<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Snap-a-Timesheet OCR — Brenda's killer feature (2026-04-29).
 *
 * Foremen take a photo of their paper timesheet (handwritten daily roster
 * with names + hours + project number on top). Office staff upload the
 * photo here and we hit Claude's vision endpoint to extract every line
 * into structured JSON: employee name (or number), date, project, ST/OT
 * hours, cost code, notes.
 *
 * Why service-layer (not controller-side):
 *   - Same logic will be reused from a future mobile/PWA upload route.
 *   - Easier to mock in tests; the controller just delegates.
 *   - Keeps audit-log writes + employee/project fuzzy matching in one place.
 *
 * The model is told to return ONLY a JSON envelope so we can parse without
 * brittle regex. We catch model deviations (markdown fences, prose) and
 * strip them before json_decode.
 */
class TimesheetOcrService
{
    /**
     * Extract timesheet entries from an uploaded image.
     *
     * @param  string  $imageBase64  Base64-encoded image bytes (no data: prefix).
     * @param  string  $mediaType    image/jpeg, image/png, image/webp.
     * @return array{
     *     entries: array<int, array<string, mixed>>,
     *     summary: string|null,
     *     raw: array<string, mixed>
     * }
     */
    public function extractFromImage(string $imageBase64, string $mediaType): array
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            throw new \RuntimeException(
                'ANTHROPIC_API_KEY is not set. Add it to .env so the Snap-a-Timesheet feature can call Claude.'
            );
        }

        // Build catalog hints so the model can prefer real employee numbers /
        // project numbers when transcribing fuzzy handwriting. Capped to keep
        // the prompt short — at scale we'd switch to RAG-style retrieval.
        $employees = Employee::where('status', 'active')
            ->orderBy('employee_number')
            ->limit(500)
            ->get(['employee_number', 'first_name', 'last_name'])
            ->map(fn ($e) => trim($e->employee_number . ' ' . $e->first_name . ' ' . $e->last_name))
            ->implode("\n");

        $projects = Project::whereIn('status', ['active', 'awarded', 'bidding'])
            ->orderBy('project_number')
            ->limit(200)
            ->get(['project_number', 'name'])
            ->map(fn ($p) => trim(($p->project_number ?? '?') . ' — ' . $p->name))
            ->implode("\n");

        $systemPrompt = <<<PROMPT
You are an OCR + data extractor for handwritten construction payroll timesheets.

Your job: read the image and return a JSON object describing every labor row visible. Be tolerant of messy handwriting, smudges, and cell overlaps.

OUTPUT FORMAT — return ONLY this JSON shape, no prose, no markdown fences:
{
  "summary": "<one short sentence about what you saw — e.g. 'Daily timesheet for BM-5400, 5 employees, 2026-04-15'>",
  "common": {
    "date": "YYYY-MM-DD or null",
    "project_hint": "<best-guess project number/name as written, or null>",
    "shift_hint": "<day/night/swing or null>"
  },
  "entries": [
    {
      "employee_name": "<as written>",
      "employee_number_hint": "<if a number is on the line, else null>",
      "regular_hours": <number, default 0>,
      "overtime_hours": <number, default 0>,
      "double_time_hours": <number, default 0>,
      "cost_code_hint": "<as written or null>",
      "craft_hint": "<as written or null>",
      "earnings_category": "HE | HO | VA  (default HE)",
      "notes": "<free-text from the row, or null>",
      "confidence": <0..1, your confidence in this row>
    }
  ]
}

RULES:
- If a header on the form has the date, copy it into common.date AND every entry's date is implied (don't repeat per row).
- Hours can be in fractional form ("8.5", "8 1/2", "8:30"). Convert to decimals.
- If a row only has total hours with no ST/OT split, put it all in regular_hours.
- "OT" or a circled OT marker → overtime_hours.
- "PR" or "DT" → double_time_hours.
- "HOL", "Hol", "Holiday" → earnings_category = "HO" and put hours in regular_hours.
- "VAC" / "Vac" / "Vacation" → "VA".
- Otherwise default to "HE".
- If the same person appears twice (e.g. one ST line + one OT line), emit two entries.
- confidence: 0.9+ for clear print, 0.6-0.8 for ordinary handwriting, <0.5 if you're guessing.

EMPLOYEE CATALOG (active workers — prefer matches when transcribing names):
{$employees}

ACTIVE PROJECTS:
{$projects}

Return ONLY the JSON. No explanation, no code fences.
PROMPT;

        $payload = [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 4096,
            'system'     => $systemPrompt,
            'messages'   => [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image',
                        'source' => [
                            'type'       => 'base64',
                            'media_type' => $mediaType,
                            'data'       => $imageBase64,
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => 'Extract every timesheet row from this image and return the JSON envelope described in your instructions.',
                    ],
                ],
            ]],
        ];

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => config('services.anthropic.version'),
            'content-type'      => 'application/json',
        ])
            ->timeout(60)
            ->post(config('services.anthropic.base_url') . '/messages', $payload);

        if (! $response->successful()) {
            Log::error('Anthropic API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException(
                'Claude API call failed (HTTP ' . $response->status() . '). ' . substr($response->body(), 0, 200)
            );
        }

        $body = $response->json();

        // The vision response comes back as content[].text — grab the first text block.
        $rawText = '';
        foreach (($body['content'] ?? []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $rawText = $block['text'];
                break;
            }
        }

        $parsed = $this->parseJsonEnvelope($rawText);

        // Apply server-side fuzzy matching so the UI can pre-select employees
        // and projects. We don't trust the model with our DB IDs.
        $parsed['entries'] = array_map(
            fn ($e) => $this->matchEntry($e, $parsed['common'] ?? []),
            $parsed['entries'] ?? []
        );

        return [
            'entries' => $parsed['entries'],
            'summary' => $parsed['summary'] ?? null,
            'common'  => $parsed['common'] ?? [],
            'raw'     => $body,
        ];
    }

    /**
     * Strip markdown fences / leading prose and json_decode the envelope.
     * Returns an array even on failure (with empty entries) so the caller
     * can surface a clean error to the user.
     */
    protected function parseJsonEnvelope(string $text): array
    {
        $trimmed = trim($text);

        // Strip ```json … ``` fences if the model added them despite the system prompt.
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $trimmed);
            $trimmed = trim($trimmed);
        }

        // Slice from the first '{' to the last '}' to drop any stray prose.
        $first = strpos($trimmed, '{');
        $last  = strrpos($trimmed, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $trimmed = substr($trimmed, $first, $last - $first + 1);
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            Log::warning('OCR: failed to parse JSON envelope', ['text' => substr($text, 0, 500)]);
            return ['entries' => [], 'summary' => 'Could not parse the AI response.', 'common' => []];
        }

        return $decoded;
    }

    /**
     * Match an extracted row against the live Employee + Project catalog.
     * Returns the entry augmented with employee_id/project_id (when matched),
     * a `match_status` flag (matched / guessed / unmatched), and a normalized
     * date pulled from `common.date` if the row didn't carry its own.
     *
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>  $common
     * @return array<string, mixed>
     */
    protected function matchEntry(array $entry, array $common): array
    {
        // Employee match
        $entry['employee_id']     = null;
        $entry['employee_label']  = $entry['employee_name'] ?? null;
        $entry['match_status']    = 'unmatched';

        // 1) Try employee_number_hint (digits stripped of leading zeros) first
        if (! empty($entry['employee_number_hint'])) {
            $hint = trim((string) $entry['employee_number_hint']);
            $emp = Employee::where('employee_number', $hint)
                ->orWhere('employee_number', ltrim($hint, '0'))
                ->first();
            if ($emp) {
                $entry['employee_id']    = $emp->id;
                $entry['employee_label'] = trim($emp->first_name . ' ' . $emp->last_name) . ' (#' . $emp->employee_number . ')';
                $entry['match_status']   = 'matched';
            }
        }

        // 2) Fall back to fuzzy name match. Try increasingly relaxed
        //    queries; only commit to a match when EXACTLY one row comes
        //    back (otherwise mark unmatched and let the user pick).
        if (! $entry['employee_id'] && ! empty($entry['employee_name'])) {
            $name  = trim((string) $entry['employee_name']);
            $parts = preg_split('/\s+/', $name);
            $first = $parts[0] ?? '';
            $last  = count($parts) > 1 ? end($parts) : '';

            $tryMatch = function (callable $constraint) {
                return Employee::query()
                    ->where('status', 'active')
                    ->where($constraint)
                    ->limit(2)
                    ->get();
            };

            // 2a) Exact-ish first + last (best signal)
            $candidate = $first && $last
                ? $tryMatch(fn ($q) => $q->where('first_name', 'like', $first . '%')
                                         ->where('last_name', 'like', $last . '%'))
                : collect();

            // 2b) If still ambiguous, try last name only — only commit
            //     when there's a single Thompson on the active roster.
            if ($candidate->count() !== 1 && $last) {
                $candidate = $tryMatch(fn ($q) => $q->where('last_name', 'like', $last . '%'));
            }

            // 2c) Solo-name input (e.g. "Marcus" with no surname)
            if ($candidate->count() !== 1 && $first && ! $last) {
                $candidate = $tryMatch(fn ($q) => $q->where('first_name', 'like', $first . '%'));
            }

            if ($candidate->count() === 1) {
                $emp = $candidate->first();
                $entry['employee_id']    = $emp->id;
                $entry['employee_label'] = trim($emp->first_name . ' ' . $emp->last_name) . ' (#' . $emp->employee_number . ')';
                $entry['match_status']   = 'guessed';
            }
        }

        // Project match — use the common project hint at the form level
        $entry['project_id'] = null;
        if (! empty($common['project_hint'])) {
            $hint = trim((string) $common['project_hint']);
            // Pull a number-like substring out of the hint
            preg_match('/[A-Za-z]{0,4}-?\d{2,}-?\d*/', $hint, $m);
            $candidate = $m[0] ?? $hint;

            $proj = Project::where('project_number', $candidate)
                ->orWhere('project_number', 'like', $candidate . '%')
                ->orWhere('name', 'like', '%' . $hint . '%')
                ->first();
            if ($proj) {
                $entry['project_id']    = $proj->id;
                $entry['project_label'] = ($proj->project_number ?? '—') . ' — ' . $proj->name;
            }
        }

        // Date: bubble up the form-level date if the entry didn't carry one
        if (empty($entry['date']) && ! empty($common['date'])) {
            $entry['date'] = $common['date'];
        }

        // Normalize numeric fields
        foreach (['regular_hours', 'overtime_hours', 'double_time_hours'] as $f) {
            $entry[$f] = (float) ($entry[$f] ?? 0);
        }

        return $entry;
    }
}
