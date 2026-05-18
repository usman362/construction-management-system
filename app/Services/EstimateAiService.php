<?php

namespace App\Services;

use App\Models\CostCode;
use App\Models\EstimateLine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Estimate Builder (Brenda — Phase 6 recommendation, 2026-05-12).
 *
 * Brenda pastes a scope of work / RFP into a modal on the estimate
 * edit page. We send the scope to Groq Llama 4 Scout along with a
 * compact catalog of:
 *   - Active cost codes (so suggestions reference real codes, not made-up
 *     ones the user would have to clean up).
 *   - Recent estimate lines deduped by description so the AI sees how
 *     similar items have been priced before (gives it a unit_cost prior).
 *
 * Llama returns a JSON envelope grouping line items by section
 * (Mobilization / Labor / Materials / Equipment / etc.). The UI shows
 * the suggestions with checkboxes so Brenda can selectively add
 * lines — no auto-commit to the estimate.
 *
 * Same Groq config (GROQ_API_KEY / GROQ_MODEL) as Snap-a-Timesheet,
 * Snap-an-Invoice and the AI Daily Log Generator.
 */
class EstimateAiService
{
    /**
     * @return array{
     *     summary: string|null,
     *     sections: array<int, array{name:string, lines: array<int, array<string,mixed>>}>,
     *     raw: array<string,mixed>
     * }
     */
    public function suggestFromScope(string $scope, array $context = []): array
    {
        $scope = trim($scope);
        if ($scope === '') {
            return ['summary' => 'No scope provided.', 'sections' => [], 'raw' => []];
        }

        $systemPrompt = $this->buildSystemPrompt($context);
        [$rawText, $rawBody] = $this->callGroq($systemPrompt, $scope);
        $parsed = $this->parseJsonEnvelope($rawText);

        return [
            'summary'  => $parsed['summary']  ?? null,
            'sections' => $this->normalizeSections($parsed['sections'] ?? []),
            'raw'      => $rawBody,
        ];
    }

    protected function buildSystemPrompt(array $context): string
    {
        // Compact catalog — top 60 active cost codes.
        $codes = CostCode::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->limit(60)
            ->get(['code', 'name'])
            ->map(fn ($c) => trim($c->code . ' — ' . $c->name))
            ->implode("\n");

        // Past line-item samples: dedupe by lowercased description, keep the
        // most recent ~80. Gives the model a unit_cost prior + format hints.
        $priors = EstimateLine::query()
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->orderByDesc('id')
            ->limit(300)
            ->get(['description', 'unit_cost', 'quantity', 'unit', 'hours', 'cost_code_id'])
            ->unique(fn ($l) => strtolower(trim($l->description)))
            ->take(80)
            ->map(fn ($l) => sprintf('"%s" — qty %s %s @ $%s/each, %s hrs',
                \Illuminate\Support\Str::limit($l->description, 60),
                rtrim(rtrim((string) (float) $l->quantity, '0'), '.'),
                $l->unit ?: '',
                rtrim(rtrim((string) (float) $l->unit_cost, '0'), '.'),
                rtrim(rtrim((string) (float) $l->hours, '0'), '.'),
            ))
            ->implode("\n");

        $clientName = $context['client_name']   ?? null;
        $projectName= $context['project_name']  ?? null;
        $extraCtx   = ($clientName ? "Client: {$clientName}\n" : '')
                    . ($projectName ? "Project: {$projectName}\n" : '');

        return <<<PROMPT
You are a senior cost estimator at an industrial construction firm. The user
will paste a scope-of-work or RFP excerpt. Your job: propose a structured
estimate (sections + line items) using REAL data from this company's catalog
where possible.

OUTPUT FORMAT — return ONLY this JSON shape, no prose, no markdown fences:
{
  "summary": "<one short sentence about your read of the scope>",
  "sections": [
    {
      "name": "<section name, e.g. 'Mobilization', 'Labor', 'Materials', 'Equipment', 'Subcontractor'>",
      "lines": [
        {
          "description":  "<concise line item description>",
          "cost_code":    "<best-match code from the catalog below, or null>",
          "quantity":     <number, default 1>,
          "unit":         "<EA | HR | LF | SF | LS | DAY | etc.>",
          "unit_cost":    <number — your best estimate based on the priors>,
          "hours":        <number of labor hours if this is a labor line, else 0>,
          "notes":        "<assumption or callout, or null>",
          "confidence":   <0..1, how confident you are about unit_cost>
        }
      ]
    }
  ]
}

RULES:
- Prefer cost codes from the catalog. If nothing matches, set cost_code to null.
- Use the prior unit costs in the priors list as your anchor — don't invent
  wildly different numbers. If the prior unit cost is \$45/HR for welder labor,
  stay in that ballpark unless the scope clearly demands a different craft.
- Sections: group by trade — Mobilization / Labor / Materials / Equipment /
  Subcontractor / General Conditions. 3-7 sections is healthy. Don't force a
  section if no lines fit.
- Quantities: be specific. "Approx 200 LF of pipe" → quantity 200, unit "LF".
  "Crew of 4 for 2 weeks (80 hrs ea)" → 1 line with hours = 320.
- Confidence: 0.9+ when you can pull both the line item AND the cost from
  the priors. 0.6-0.8 for educated guesses. <0.5 if you're stretching.
- DO NOT add markup / profit / overhead lines unless explicitly asked — the
  app handles markup at the line level.
- DO NOT include taxes or contingency unless mentioned in the scope.

{$extraCtx}
COST CODE CATALOG:
{$codes}

PRIOR LINE ITEMS (descriptions, qty, unit_cost, hours — your anchor for pricing):
{$priors}

Return ONLY the JSON. No explanation, no code fences.
PROMPT;
    }

    /**
     * @return array{0:string, 1:array}
     */
    protected function callGroq(string $systemPrompt, string $scope): array
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
            'model'           => $model,
            'temperature'     => 0.2,
            'max_tokens'      => 4096,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => "Scope of work:\n\n" . $scope],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(90)->post($url, $payload);

        if (! $response->successful()) {
            Log::error('Groq API error (estimate ai)', [
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
            throw new \RuntimeException('Groq returned empty content. Try a longer / clearer scope.');
        }
        return [$rawText, $body];
    }

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
            Log::warning('AI estimate: could not parse JSON envelope', ['text' => substr($text, 0, 500)]);
            return ['sections' => [], 'summary' => 'Could not parse the AI response.'];
        }
        return $decoded;
    }

    /**
     * Normalize each section + line. Resolve the suggested cost_code STRING
     * to a real cost_code_id from the DB so the front-end can pre-select it.
     */
    protected function normalizeSections(array $sections): array
    {
        // Build a one-time code → id map so we don't query in a loop.
        $codeIdMap = CostCode::query()
            ->where('is_active', true)
            ->pluck('id', 'code')
            ->mapWithKeys(fn ($id, $code) => [strtoupper(trim($code)) => $id])
            ->all();

        $out = [];
        foreach ($sections as $sec) {
            if (! is_array($sec)) continue;
            $name = trim((string) ($sec['name'] ?? '')) ?: 'Untitled';
            $lines = [];
            foreach ($sec['lines'] ?? [] as $l) {
                if (! is_array($l)) continue;
                $codeStr = strtoupper(trim((string) ($l['cost_code'] ?? '')));
                $lines[] = [
                    'description'  => trim((string) ($l['description'] ?? '')),
                    'cost_code'    => $codeStr !== '' ? $codeStr : null,
                    'cost_code_id' => $codeStr !== '' ? ($codeIdMap[$codeStr] ?? null) : null,
                    'quantity'     => (float) ($l['quantity'] ?? 1),
                    'unit'         => trim((string) ($l['unit'] ?? '')) ?: 'EA',
                    'unit_cost'    => (float) ($l['unit_cost'] ?? 0),
                    'hours'        => (float) ($l['hours'] ?? 0),
                    'notes'        => trim((string) ($l['notes'] ?? '')) ?: null,
                    'confidence'   => max(0.0, min(1.0, (float) ($l['confidence'] ?? 0.5))),
                    'amount'       => (float) ($l['quantity'] ?? 1) * (float) ($l['unit_cost'] ?? 0),
                ];
            }
            if (! empty($lines)) {
                $out[] = ['name' => $name, 'lines' => $lines];
            }
        }
        return $out;
    }
}
