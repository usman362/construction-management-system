<?php

namespace App\Concerns;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Reusable Excel export pipeline. Any controller can `use ExportsToExcel;` then
 * call `$this->streamExcel()` from an `export()` action to generate a styled
 * XLSX from any iterable of rows.
 *
 * Column definition shape:
 *   [
 *       'header' => 'Project #',          // header cell text
 *       'value'  => fn ($row) => $row->project_number,  // closure resolving the cell value
 *       'format' => 'currency',           // optional: text|number|currency|date|percent
 *       'width'  => 15,                   // optional column width (chars)
 *       'align'  => 'right',              // optional: left|center|right
 *   ]
 *
 * Output:
 *   Returns a Symfony BinaryFileResponse that auto-deletes the temp file after
 *   download — caller just `return $this->streamExcel(...)` from the action.
 */
trait ExportsToExcel
{
    /**
     * Build and stream an XLSX file from a collection of rows + column defs.
     *
     * @param string                  $filename     Output filename (without dir)
     * @param string                  $sheetName    Sheet tab label
     * @param iterable<int, mixed>    $rows         Models or arrays
     * @param array<int, array>       $columns      Column definitions (see trait docblock)
     * @param string|null             $title        Optional title row above the headers
     */
    protected function streamExcel(
        string $filename,
        string $sheetName,
        iterable $rows,
        array $columns,
        ?string $title = null,
    ): BinaryFileResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetName, 0, 31));

        $rowCursor = 1;

        // Optional title block — useful for project-scoped exports
        // ("Cost Report — BM-5403 — Apr 25, 2026").
        if ($title) {
            $endCol = $this->columnLetter(count($columns));
            $sheet->setCellValue("A{$rowCursor}", $title);
            $sheet->mergeCells("A{$rowCursor}:{$endCol}{$rowCursor}");
            $sheet->getStyle("A{$rowCursor}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($rowCursor)->setRowHeight(22);
            $rowCursor += 2;   // blank spacer row
        }

        $headerRow = $rowCursor;

        // ─── Header row ────────────────────────────────────────────
        foreach ($columns as $i => $col) {
            $letter = $this->columnLetter($i + 1);
            $sheet->setCellValue("{$letter}{$headerRow}", $col['header'] ?? '');
            if (!empty($col['width'])) {
                $sheet->getColumnDimension($letter)->setWidth($col['width']);
            } else {
                $sheet->getColumnDimension($letter)->setAutoSize(true);
            }
        }
        $endCol = $this->columnLetter(count($columns));
        $sheet->getStyle("A{$headerRow}:{$endCol}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1E3A8A']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);
        $sheet->freezePane("A" . ($headerRow + 1));    // sticky header

        // ─── Data rows ─────────────────────────────────────────────
        $dataRow = $headerRow + 1;
        foreach ($rows as $row) {
            foreach ($columns as $i => $col) {
                $letter = $this->columnLetter($i + 1);
                $value  = $this->resolveValue($row, $col);
                $cell   = "{$letter}{$dataRow}";

                $sheet->setCellValue($cell, $value);
                $this->applyCellFormat($sheet, $cell, $col['format'] ?? null, $col['align'] ?? null);
            }
            $dataRow++;
        }

        // Subtle row borders for readability
        if ($dataRow > $headerRow + 1) {
            $sheet->getStyle("A" . ($headerRow + 1) . ":{$endCol}" . ($dataRow - 1))
                ->applyFromArray([
                    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E5E7EB']]],
                ]);
        }

        // ─── Save to temp file + stream as download ─────────────────
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmp);

        $response = response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);

        // Force "attachment" disposition — some browsers try to render xlsx inline
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );

        return $response;
    }

    /**
     * Convert column index (1-based) to spreadsheet letter (A, B, ..., Z, AA…).
     */
    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index  = (int) (($index - $mod) / 26);
        }
        return $letter;
    }

    /**
     * Resolve a cell value — supports closures, dot-paths, or static strings.
     */
    private function resolveValue(mixed $row, array $col): mixed
    {
        $val = $col['value'] ?? null;
        if ($val instanceof \Closure) {
            return $val($row);
        }
        if (is_string($val) && str_contains($val, '.')) {
            // dot-path lookup, e.g. 'client.name'
            $parts = explode('.', $val);
            $cursor = $row;
            foreach ($parts as $p) {
                if (is_object($cursor)) { $cursor = $cursor->{$p} ?? null; }
                elseif (is_array($cursor)) { $cursor = $cursor[$p] ?? null; }
                else { return null; }
            }
            return $cursor;
        }
        if (is_string($val)) {
            return is_object($row) ? ($row->{$val} ?? null) : ($row[$val] ?? null);
        }
        return null;
    }

    private function applyCellFormat($sheet, string $cell, ?string $format, ?string $align): void
    {
        if ($format) {
            $code = match ($format) {
                'currency' => '"$"#,##0.00',
                'number'   => '#,##0.00',
                'integer'  => '#,##0',
                'percent'  => '0.00%',
                'date'     => 'yyyy-mm-dd',
                'datetime' => 'yyyy-mm-dd hh:mm',
                default    => null,
            };
            if ($code) {
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($code);
            }
        }

        if ($align) {
            $h = match ($align) {
                'right'  => Alignment::HORIZONTAL_RIGHT,
                'center' => Alignment::HORIZONTAL_CENTER,
                default  => Alignment::HORIZONTAL_LEFT,
            };
            $sheet->getStyle($cell)->getAlignment()->setHorizontal($h);
        } elseif (in_array($format, ['currency', 'number', 'integer', 'percent'], true)) {
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }
}
