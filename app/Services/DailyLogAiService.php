<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Daily Log Generator (Brenda — Phase 2, 2026-05-12).
 *
 * Foreman opens the mobile daily-log page, taps "🎤 AI Dictation", and
 * speaks naturally for 20-60 seconds: "It rained this morning, about
 * 62 degrees, crew of 6 on tank welding, delivery from Sherwin showed
 * up around 10, OSHA inspector swung by, no incidents but one
 * near-miss when a hose snapped."
 *
 * Browser SpeechRecognition transcribes locally → we send the
 * transcript to Groq Llama 4 Scout → it returns a structured JSON
 * envelope mapping the speech to every DailyLog form field. The UI
 * pre-fills the form; foreman reviews + hits Save.
 *
 * Why text → Groq chat (not audio → Whisper):
 *   - Browser SpeechRecognition is free, fast, and already wired on
 *     this page (mobile-create.blade.php).
 *   - Avoids uploading audio over potentially slow site connections.
 *   - Llama text-only is much faster than whisper round-trip.
 *
 * Same Groq config (GROQ_API_KEY / GROQ_MODEL) as TimesheetOcrService.
 */
class DailyLogAiService
{
    /**
     * Parse a free-form voice transcript into structured daily-log fields.
     *
     * @param  string  $transcript  Raw text the foreman dictated.
     * @return array{
     *     fields: array<string, mixed>,
     *     summary: string|null,
     *     raw: array<string, mixed>
     * }
     */
    public function extractFromTranscript(string $transcript): array
    {
        $clean = trim($transcript);
        if ($clean === '') {
            return ['fields' => [], 'summary' => 'No transcript provided.', 'raw' => []];
        }

        [$rawText, $rawBody] = $this->callGroq($this->buildSystemPrompt(), $clean);
        $parsed = $this->parseJsonEnvelope($rawText);

        return [
            'fields'  => $this->normalizeFields($parsed['fields'] ?? []),
            'summary' => $parsed['summary'] ?? null,
            'raw'     => $rawBody,
        ];
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You convert a construction foreman's free-form voice dictation into a structured Daily Log JSON.

OUTPUT FORMAT — return ONLY this JSON shape, no prose, no markdown fences:
{
  "summary": "<one short sentence about what you heard>",
  "fields": {
    "weather": "sunny | cloudy | rainy | snowy | foggy | windy | null",
    "temperature": <integer °F or null>,
    "temperature_high": <integer °F or null>,
    "temperature_low": <integer °F or null>,
    "precipitation": "<text like '0.25 in' or null>",
    "wind_speed": "<integer mph as text or null>",
    "notes": "<cleaned-up multi-line summary of work performed, deliveries, crew activity. Use bullet-style hyphens for separate items. PRESERVE the foreman's voice — don't over-edit.>",
    "visitors": "<comma-separated list of any visitors mentioned (inspectors, owner reps, etc.) or null>",
    "safety_issues": "<text describing safety issues / near-misses mentioned, or null>",
    "incidents_count": <integer count of incidents mentioned, default 0>,
    "near_misses_count": <integer count of near-misses mentioned, default 0>,
    "delays": "<text describing weather delays / late deliveries / disruptions, or null>"
  }
}

RULES:
- Weather: choose the closest enum value. "Drizzle" / "thunderstorm" / "showers" → rainy. "Overcast" / "partly cloudy" → cloudy. "Clear" / "sunny" → sunny. If not mentioned, set null.
- Temperature: extract the °F number ("around 62", "in the 60s"→62, "low 70s"→72). Null if not mentioned.
- Notes: this is the meat — pull every work-related thing they said (what got done, who did what, deliveries, materials used, equipment moved). Format as a few short hyphen-bulleted lines.
- Visitors: anybody by name or title mentioned as visiting the site (inspector, client, vendor, etc.). Null if none.
- Safety issues: anything that sounded like a safety concern, hazard, near-miss, incident, injury. Null if all clear.
- Incidents vs near-misses: incidents = actual injuries / equipment damage. Near-misses = "almost happened" events. Count them. If the foreman says "no incidents", set 0.
- Delays: anything that slowed work — rain stoppage, missing material, late truck, broken equipment, RFI waiting on engineer.

DO NOT invent facts. If the foreman didn't mention something, leave it null / empty / 0.
DO preserve numbers exactly as spoken ("crew of 6" → mention "crew of 6" in notes).
DO clean up "uh", "um", false starts, and rambling — keep the substance.

Return ONLY the JSON. No explanation, no code fences.
PROMPT;
    }

    /**
     * Call Groq Cloud — text-only chat completion (no vision).
     *
     * @return array{0:string, 1:array}  [raw text response, full body]
     */
    protected function callGroq(string $systemPrompt, string $transcript): array
    {
        $apiKey = config('services.groq.api_key');
        if (empty($apiKey)) {
            throw new \RuntimeException(
                'GROQ_API_KEY is not set. Get a free key at https://console.groq.com/keys and add to .env.'
            );
        }

        $model = config('services.groq.model', 'meta-llama/llama-4-scout-17b-16e-instruct');
        $url   = config('services.groq.base_url') . '/chat/completions';

        $payload = [
            'model'       => $model,
            'temperature' => 0.1,
            'max_tokens'  => 2048,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => "Foreman dictation:\n\n" . $transcript],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post($url, $payload);

        if (! $response->successful()) {
            Log::error('Groq API error (daily log)', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException(
                'Groq API call failed (HTTP ' . $response->status() . '). ' . substr($response->body(), 0, 300)
            );
        }

        $body = $response->json();
        $rawText = $body['choices'][0]['message']['content'] ?? '';
        if ($rawText === '') {
            throw new \RuntimeException('Groq returned empty content. Try a longer / clearer dictation.');
        }
        return [$rawText, $body];
    }

    /**
     * Strip markdown fences / leading prose and json_decode.
     */
    protected function parseJsonEnvelope(string $text): array
    {
        $trimmed = trim($text);
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $trimmed);
            $trimmed = trim($trimmed);
        }
        $first = strpos($trimmed, '{');
        $last  = strrpos($trimmed, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $trimmed = substr($trimmed, $first, $last - $first + 1);
        }
        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            Log::warning('AI daily log: could not parse JSON envelope', ['text' => substr($text, 0, 500)]);
            return ['fields' => [], 'summary' => 'Could not parse the AI response.'];
        }
        return $decoded;
    }

    /**
     * Normalize the AI's output so the front-end can plug it straight
     * into form inputs:
     *   - Coerce numbers
     *   - Default empty optional fields to null (not "")
     *   - Validate weather enum against our DailyLog schema
     */
    protected function normalizeFields(array $f): array
    {
        $allowedWeather = ['sunny', 'cloudy', 'rainy', 'snowy', 'foggy', 'windy'];
        $weather = isset($f['weather']) ? strtolower(trim((string) $f['weather'])) : '';
        if (! in_array($weather, $allowedWeather, true)) $weather = null;

        $toIntOrNull = fn ($v) => (is_numeric($v) ? (int) $v : null);

        return [
            'weather'           => $weather,
            'temperature'       => $toIntOrNull($f['temperature']      ?? null),
            'temperature_high'  => $toIntOrNull($f['temperature_high'] ?? null),
            'temperature_low'   => $toIntOrNull($f['temperature_low']  ?? null),
            'precipitation'     => $this->blankToNull($f['precipitation'] ?? null),
            'wind_speed'        => $this->blankToNull($f['wind_speed']    ?? null),
            'notes'             => trim((string) ($f['notes']            ?? '')) ?: null,
            'visitors'          => $this->blankToNull($f['visitors']        ?? null),
            'safety_issues'     => $this->blankToNull($f['safety_issues']   ?? null),
            'incidents_count'   => max(0, $toIntOrNull($f['incidents_count']   ?? 0) ?? 0),
            'near_misses_count' => max(0, $toIntOrNull($f['near_misses_count'] ?? 0) ?? 0),
            'delays'            => $this->blankToNull($f['delays']          ?? null),
        ];
    }

    protected function blankToNull(mixed $v): ?string
    {
        $s = trim((string) ($v ?? ''));
        return $s === '' || strtolower($s) === 'null' ? null : $s;
    }
}
