<?php
/**
 * ExportService — Centralized export engine for the Student Management System.
 * Handles both Excel (.xlsx) and PDF exports for admin-only use.
 *
 * Usage:
 *   ExportService::excel($headers, $rows, $filename, $sheetName);
 *   ExportService::pdf($title, $headers, $rows, $filename, $meta);
 */

define('FPDF_FONTPATH', __DIR__ . '/font/');
require_once __DIR__ . '/XlsxWriter.php';
require_once __DIR__ . '/fpdf.php';

class ExportService
{
    // ═══════════════════════════════════════════
    // EXCEL EXPORT
    // ═══════════════════════════════════════════

    /**
     * @param array  $headers   ['Column Name' => 'string'|'integer'|'float', ...]
     * @param array  $rows      [ [val1, val2, ...], ... ]
     * @param string $filename  e.g. 'students_2026-06-14.xlsx'
     * @param string $sheetName Sheet tab label
     */
    public static function excel(array $headers, array $rows, string $filename, string $sheetName = 'Data'): void
    {
        $writer = new XlsxWriter();
        $writer->writeSheetHeader($sheetName, $headers);

        foreach ($rows as $row) {
            $writer->writeSheetRow($sheetName, array_values($row));
        }

        $writer->writeToFile($filename);
        exit;
    }

    // ═══════════════════════════════════════════
    // PDF EXPORT  — uses FPDF (real binary PDF)
    // ═══════════════════════════════════════════

    /**
     * @param string $title     Report title, e.g. "Students Report"
     * @param array  $headers   ['Col1', 'Col2', ...]  column labels
     * @param array  $rows      [ [val1, val2, ...], ... ]
     * @param string $filename  e.g. 'students_2026-06-14.pdf'
     * @param array  $meta      ['institute' => '...', 'subtitle' => '...']
     */
    public static function pdf(string $title, array $headers, array $rows, string $filename, array $meta = []): void
    {
        // ── Build column widths ──────────────────────────────────────
        $colCount   = count($headers);
        $pageWidth  = 277; // A4 landscape usable width (mm)
        $colWidth   = $colCount > 0 ? floor($pageWidth / $colCount) : $pageWidth;
        $colWidths  = array_fill(0, $colCount, $colWidth);

        // ── Create PDF ───────────────────────────────────────────────
        $pdf = new ExportPDF('L', 'mm', 'A4'); // Landscape A4
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setMeta($title, $meta, $headers, $colWidths);
        $pdf->AddPage();

        // ── Table rows ───────────────────────────────────────────────
        $pdf->SetFont('Arial', '', 8);
        $fill = false;
        foreach ($rows as $row) {
            $rowArr = array_values($row);
            
            // Calculate row height for multi-line cells
            $maxLines = 1;
            foreach ($rowArr as $ci => $cell) {
                $w = $colWidths[$ci] ?? $colWidth;
                $text = (string)($cell ?? '');
                // Account for FPDF line wrapping and explicit newlines
                $lines = max(1, ceil($pdf->GetStringWidth($text) / ($w - 2)));
                $lines = max($lines, substr_count($text, "\n") + 1);
                if ($lines > $maxLines) $maxLines = $lines;
            }
            $lineH = 6;
            // Cap at 4 lines to prevent massive rows
            $rowHeight = $lineH * max(1, min($maxLines, 4));

            // Page break check
            if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 20) {
                $pdf->AddPage();
            }

            if ($fill) {
                $pdf->SetFillColor(245, 245, 250);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            $startX = $pdf->GetX();
            $startY = $pdf->GetY();

            foreach ($rowArr as $ci => $cell) {
                $w = $colWidths[$ci] ?? $colWidth;
                $text = (string)($cell ?? '');
                
                // Draw cell background and border
                $pdf->Rect($startX, $startY, $w, $rowHeight, $fill ? 'DF' : 'D');
                
                // Set position to print text
                $pdf->SetXY($startX, $startY);
                // Print text (we use MultiCell with standard line height, border=0 since we drew Rect)
                $pdf->MultiCell($w, $lineH, $text, 0, 'L');
                
                $startX += $w;
            }
            
            // Move Y to next row
            $pdf->SetXY(10, $startY + $rowHeight);
            $fill = !$fill;
        }

        // ── Empty state ──────────────────────────────────────────────
        if (empty($rows)) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell($pageWidth, 10, 'No records found.', 0, 1, 'C');
        }

        // ── Output ───────────────────────────────────────────────────
        $pdf->Output('D', $filename);
        exit;
    }
}

// ═══════════════════════════════════════════════════
// Custom PDF class with styled header and footer
// ═══════════════════════════════════════════════════
class ExportPDF extends FPDF
{
    private string $reportTitle = '';
    private array  $meta        = [];
    private array  $colHeaders  = [];
    private array  $colWidths   = [];

    public function setMeta(string $title, array $meta, array $headers, array $widths): void
    {
        $this->reportTitle = $title;
        $this->meta        = $meta;
        $this->colHeaders  = $headers;
        $this->colWidths   = $widths;
    }

    public function Header(): void
    {
        $institute = $this->meta['institute'] ?? 'Student Management System';

        // ── Top banner (dark background) ──
        $this->SetFillColor(15, 23, 42); // #0f172a — matches sidebar
        $this->Rect(0, 0, $this->GetPageWidth(), 22, 'F');

        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(4);
        $this->Cell(0, 8, $institute, 0, 1, 'C');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(180, 210, 255);
        $this->Cell(0, 5, $this->reportTitle, 0, 1, 'C');

        // Subtitle / meta line
        $subtitle = $this->meta['subtitle'] ?? '';
        $genDate  = 'Generated: ' . date('d M Y, h:i A');
        $metaLine = $subtitle ? $subtitle . '   |   ' . $genDate : $genDate;

        $this->SetY(28);
        $this->SetFont('Arial', 'I', 7.5);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, $metaLine, 0, 1, 'R');

        $this->Ln(2);

        // ── Column header row ──────────────────────────
        $this->SetFillColor(30, 64, 175);   // #1e40af — rich blue
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->SetDrawColor(200, 200, 220);

        foreach ($this->colHeaders as $i => $header) {
            $w = $this->colWidths[$i] ?? 30;
            $this->Cell($w, 8, strtoupper($header), 1, 0, 'C', true);
        }
        $this->Ln();

        // Reset text color for body
        $this->SetTextColor(30, 30, 30);
        $this->SetDrawColor(200, 200, 220);
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(150, 150, 150);
        $this->SetFillColor(240, 240, 245);
        $this->Rect(0, $this->GetPageHeight() - 14, $this->GetPageWidth(), 14, 'F');
        $this->Cell(0, 6, 'Page ' . $this->PageNo() . ' | Student Management System — Confidential', 0, 0, 'C');
    }
}
