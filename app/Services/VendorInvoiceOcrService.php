<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Snap-a-Vendor-Invoice OCR (Brenda 2026-05-10).
 *
 *   "We could do the AI thing (image capture) for the vendor invoices as
 *    well." — same flow as Snap-a-Timesheet but for vendor bills:
 *    upload a photo or scan of an invoice, AI extracts the header fields
 *    (vendor, invoice #, date, amount, PO reference) and any visible line
 *    items, returns structured JSON the office confirms before saving.
 *
 * Provider: Groq Cloud (same Llama 4 Scout 17B vision model that powers
 * Snap-a-Timesheet). Re-uses GROQ_API_KEY / GROQ_MODEL from .env.
 *
 * Why a separate class instead of extending TimesheetOcrService:
 *   - Prompt is meaningfully different (invoice-specific schema)
 *   - Catalog hints are different (vendors + POs, not employees)
 *   - Keeps the two extractors independent so a tweak to one doesn't
 *     accidentally break the other.
 */
class VendorInvoiceOcrService
{
    /**
     * Extract a vendor invoice from an uploaded image.
     *
     * @param  string  $imageBase64
     * @param  string  $mediaType    image/jpeg, image/png, image/webp
     * @return array{
     *     header: array<string, mixed>,
     *     line_items: array<int, array<string, mixed>>,
     *     summary: string|null,
     *     raw: array<string, mixed>
     * }
     */
    public function extractFromImage(string $imageBase64, string $mediaType): array
    {
        $apiKey = config('services.groq.api_key');
        if (empty($apiKey)) {
            throw new \RuntimeException(
                'GROQ_API_KEY is not set. Get a free key (no credit card needed) at https://console.groq.com/keys and add to .env.'
            );
        }

        $systemPrompt = $this->buildSystemPrompt();
        $model = config('services.groq.model', 'meta-llama/llama-4-scout-17b-16e-instruct');
        $url   = config('services.groq.base_url') . '/chat/completions';

        $payload = [
            'model'           => $model,
            'temperature'     => 0.1,
            'max_tokens'      => 4096,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Extract the invoice header and line items from this image. Return the JSON envelope described in your instructions.'],
                        ['type' => 'image_url', 'image_url' => [
                            'url' => 'data:' . $mediaType . ';base64,' . $imageBase64,
                        ]],
                    ],
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])
            ->timeout(60)
            ->post($url, $payload);

        if (! $response->successful()) {
            Log::error('Groq vendor-invoice OCR error', [
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
            throw new \RuntimeException('Groq returned empty content. Try a clearer image.');
        }

        $parsed = $this->parseJsonEnvelope($rawText);

        return [
            'header'     => $this->matchVendorAndPo($parsed['header'] ?? []),
            'line_items' => $parsed['line_items'] ?? [],
            'summary'    => $parsed['summary'] ?? null,
            'raw'        => $body,
        ];
    }

    /**
     * Build the OCR system prompt. Includes the active vendor + PO catalog
     * so the model can prefer real names/numbers over its raw transcription.
     */
    protected function buildSystemPrompt(): string
    {
        $vendors = Vendor::query()
            ->orderBy('name')
            ->limit(500)
            ->pluck('name')
            ->implode("\n");

        $pos = PurchaseOrder::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get(['po_number'])
            ->pluck('po_number')
            ->filter()
            ->implode("\n");

        return <<<PROMPT
You are an OCR + data extractor for vendor invoices (bills) received by a construction company. The image is a photo, scan, or PDF screenshot of a single invoice.

Your job: read the image and return a JSON object describing the invoice header and its line items. Be tolerant of skewed scans, faint print, and stamps obscuring corners.

OUTPUT FORMAT — return ONLY this JSON, no prose, no markdown fences:
{
  "summary": "<one short sentence — e.g. 'Acme Steel invoice #12345, dated 2026-04-15, \$8,200.00'>",
  "header": {
    "vendor_name":      "<as written on the invoice's letterhead/billing-from>",
    "invoice_number":   "<the invoice's own number, often top right>",
    "invoice_date":     "<YYYY-MM-DD>",
    "due_date":         "<YYYY-MM-DD or null>",
    "po_reference":     "<the PO number printed on the invoice, or null>",
    "subtotal":         <number or null>,
    "tax_amount":       <number or null>,
    "total_amount":     <number, the invoice's grand total>,
    "currency":         "<USD or whatever's printed; default USD>",
    "vendor_address":   "<full address as printed, or null>",
    "notes":            "<terms / payment instructions / freeform memos>"
  },
  "line_items": [
    {
      "description":  "<item or service description as printed>",
      "quantity":     <number, default 1>,
      "unit_price":   <number, the per-unit cost>,
      "amount":       <number, line total>
    }
  ]
}

RULES:
- Always extract the GRAND TOTAL into header.total_amount — even if subtotal/tax aren't visible.
- Dates: convert from any format (5/4/26, May 4 2026, 04-MAY-2026) to YYYY-MM-DD.
- "Net 30", "Due upon receipt", etc. → set due_date by adding the days to invoice_date if obvious; otherwise null.
- Strip currency symbols and commas from numbers ($8,250.00 → 8250.00).
- If the same line repeats for descriptions, treat them as one item with the summed quantity.
- If vendor_name closely matches a name in the catalog below, return the catalog spelling.
- If the invoice references a PO whose number matches an entry in the PO catalog below, return that exact number.

VENDOR CATALOG (prefer these exact spellings):
{$vendors}

PURCHASE ORDER CATALOG (recent POs):
{$pos}

Return ONLY the JSON. No explanation, no code fences.
PROMPT;
    }

    /**
     * Strip markdown fences / leading prose and json_decode the envelope.
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
            Log::warning('Vendor-invoice OCR: failed to parse JSON envelope', ['text' => substr($text, 0, 500)]);
            return ['header' => [], 'line_items' => [], 'summary' => 'Could not parse the AI response.'];
        }
        return $decoded;
    }

    /**
     * Look up the AI's vendor_name and po_reference against the live tables
     * so the UI can pre-select dropdowns. Returns the header augmented with
     * vendor_id / purchase_order_id when matches are found.
     */
    protected function matchVendorAndPo(array $header): array
    {
        $header['vendor_id']         = null;
        $header['purchase_order_id'] = null;
        $header['match_status']      = ['vendor' => 'unmatched', 'po' => 'unmatched'];

        // Vendor: try exact name match, then fuzzy LIKE
        if (! empty($header['vendor_name'])) {
            $name = trim((string) $header['vendor_name']);
            $vendor = Vendor::where('name', $name)->first()
                ?? Vendor::where('name', 'like', $name . '%')->first()
                ?? Vendor::where('name', 'like', '%' . $name . '%')->first();
            if ($vendor) {
                $header['vendor_id']         = $vendor->id;
                $header['vendor_label']      = $vendor->name;
                $header['match_status']['vendor'] = 'matched';
            }
        }

        // PO: try exact po_number match
        if (! empty($header['po_reference'])) {
            $ref = trim((string) $header['po_reference']);
            $po = PurchaseOrder::where('po_number', $ref)->first()
                ?? PurchaseOrder::where('po_number', 'like', '%' . $ref . '%')->first();
            if ($po) {
                $header['purchase_order_id'] = $po->id;
                $header['po_label']          = $po->po_number;
                $header['match_status']['po'] = 'matched';
            }
        }

        // Normalize numerics
        foreach (['subtotal', 'tax_amount', 'total_amount'] as $f) {
            if (isset($header[$f])) {
                $header[$f] = (float) preg_replace('/[^\d.\-]/', '', (string) $header[$f]);
            }
        }

        return $header;
    }
}
