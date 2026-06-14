<?php
/**
 * XlsxWriter — Pure PHP .xlsx generator.
 * Uses ZipArchive if available, otherwise falls back to a
 * built-in pure-PHP ZIP implementation (no extensions required).
 */
class XlsxWriter
{
    private $sheets = [];
    private $sharedStrings = [];
    private $sharedStringCount = 0;

    // ── Public API ──────────────────────────────────────────────────
    public function writeSheetHeader(string $sheetName, array $colTypes, bool $suppressRow = false): void
    {
        if (!isset($this->sheets[$sheetName])) {
            $this->sheets[$sheetName] = ['rows' => [], 'col_types' => $colTypes, 'header' => []];
        }
        if (!$suppressRow) {
            $this->sheets[$sheetName]['header'] = array_keys($colTypes);
        }
    }

    public function writeSheetRow(string $sheetName, array $row): void
    {
        if (!isset($this->sheets[$sheetName])) {
            $this->sheets[$sheetName] = ['rows' => [], 'col_types' => [], 'header' => []];
        }
        $this->sheets[$sheetName]['rows'][] = $row;
    }

    public function writeToFile(string $filename): void
    {
        $files = $this->buildAllFiles();

        // Build binary ZIP in memory
        $zipBytes = $this->buildZip($files);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . strlen($zipBytes));
        header('Cache-Control: max-age=0');
        echo $zipBytes;
        exit;
    }

    // ── Build all file contents for the zip ─────────────────────────
    private function buildAllFiles(): array
    {
        $files = [];

        // [Content_Types].xml
        $ct  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $ct .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $ct .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $ct .= '<Default Extension="xml" ContentType="application/xml"/>';
        $ct .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $ct .= '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>';
        $ct .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $i = 1;
        foreach ($this->sheets as $name => $data) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $i++;
        }
        $ct .= '</Types>';
        $files['[Content_Types].xml'] = $ct;

        // _rels/.rels
        $rels  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $rels .= '</Relationships>';
        $files['_rels/.rels'] = $rels;

        // xl/workbook.xml
        $wb  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $wb .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $wb .= '<sheets>';
        $i = 1;
        foreach ($this->sheets as $name => $data) {
            $wb .= '<sheet name="' . $this->xe($name) . '" sheetId="' . $i . '" r:id="rId' . ($i + 2) . '"/>';
            $i++;
        }
        $wb .= '</sheets></workbook>';
        $files['xl/workbook.xml'] = $wb;

        // xl/_rels/workbook.xml.rels
        $wbr  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $wbr .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $wbr .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        $wbr .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $i = 1;
        foreach ($this->sheets as $name => $data) {
            $wbr .= '<Relationship Id="rId' . ($i + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
            $i++;
        }
        $wbr .= '</Relationships>';
        $files['xl/_rels/workbook.xml.rels'] = $wbr;

        // xl/styles.xml
        $files['xl/styles.xml'] = $this->buildStyles();

        // Sheets (must be built before shared strings)
        $i = 1;
        foreach ($this->sheets as $name => $data) {
            $files['xl/worksheets/sheet' . $i . '.xml'] = $this->buildSheet($name);
            $i++;
        }

        // Shared strings (built last because sheets populate them)
        $files['xl/sharedStrings.xml'] = $this->buildSharedStrings();

        return $files;
    }

    // ── Sheet XML ────────────────────────────────────────────────────
    private function buildSheet(string $name): string
    {
        $sheet = $this->sheets[$name];
        $xml   = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml  .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $ri = 1;

        // Header row
        if (!empty($sheet['header'])) {
            $xml .= '<row r="' . $ri . '">';
            $ci  = 0;
            foreach ($sheet['header'] as $cell) {
                $ref = $this->colLetter($ci++) . $ri;
                $si  = $this->addStr($cell);
                $xml .= '<c r="' . $ref . '" t="s" s="1"><v>' . $si . '</v></c>';
            }
            $xml .= '</row>';
            $ri++;
        }

        // Data rows
        $types = array_values($sheet['col_types'] ?? []);
        foreach ($sheet['rows'] as $row) {
            $xml .= '<row r="' . $ri . '">';
            $ci  = 0;
            foreach ($row as $cell) {
                $ref  = $this->colLetter($ci) . $ri;
                $type = $types[$ci] ?? 'string';
                if (in_array($type, ['integer', 'float', 'price']) && is_numeric($cell)) {
                    $xml .= '<c r="' . $ref . '"><v>' . $this->xe((string)$cell) . '</v></c>';
                } else {
                    $si  = $this->addStr((string)($cell ?? ''));
                    $xml .= '<c r="' . $ref . '" t="s"><v>' . $si . '</v></c>';
                }
                $ci++;
            }
            $xml .= '</row>';
            $ri++;
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    // ── Shared strings XML ───────────────────────────────────────────
    private function buildSharedStrings(): string
    {
        $total = $this->sharedStringCount;
        $xml   = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml  .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $total . '" uniqueCount="' . $total . '">';
        $byIdx = array_flip($this->sharedStrings);
        ksort($byIdx);
        foreach ($byIdx as $str) {
            $xml .= '<si><t>' . $this->xe($str) . '</t></si>';
        }
        $xml .= '</sst>';
        return $xml;
    }

    // ── Styles XML ───────────────────────────────────────────────────
    private function buildStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    // ── Helpers ──────────────────────────────────────────────────────
    private function addStr(string $s): int
    {
        if (!isset($this->sharedStrings[$s])) {
            $this->sharedStrings[$s] = $this->sharedStringCount++;
        }
        return $this->sharedStrings[$s];
    }

    private function colLetter(int $idx): string
    {
        $l = '';
        while ($idx >= 0) {
            $l   = chr(65 + ($idx % 26)) . $l;
            $idx = intval($idx / 26) - 1;
        }
        return $l;
    }

    private function xe(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    // ════════════════════════════════════════════════════════════════
    // Pure-PHP ZIP builder — works without ZipArchive extension.
    // Implements the PKZIP local file + central directory format.
    // ════════════════════════════════════════════════════════════════
    private function buildZip(array $files): string
    {
        // Try ZipArchive first (faster, handles large files better)
        if (class_exists('ZipArchive')) {
            return $this->buildZipViaZipArchive($files);
        }
        return $this->buildZipPure($files);
    }

    private function buildZipViaZipArchive(array $files): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $bytes = file_get_contents($tmp);
        unlink($tmp);
        return $bytes;
    }

    private function buildZipPure(array $files): string
    {
        $localHeaders  = '';
        $centralDir    = '';
        $offset        = 0;
        $centralCount  = 0;

        foreach ($files as $name => $content) {
            $nameBytes = $name;
            $nameLen   = strlen($nameBytes);
            $data      = $content;
            $dataLen   = strlen($data);
            $crc       = crc32($data);
            $dosDate   = $this->dosDateTime();

            // Local file header (signature 0x04034b50)
            $local  = pack('V', 0x04034b50);  // signature
            $local .= pack('v', 20);           // version needed: 2.0
            $local .= pack('v', 0);            // flags
            $local .= pack('v', 0);            // compression: stored (0)
            $local .= pack('V', $dosDate);     // mod time+date
            $local .= pack('V', $crc);         // CRC-32
            $local .= pack('V', $dataLen);     // compressed size
            $local .= pack('V', $dataLen);     // uncompressed size
            $local .= pack('v', $nameLen);     // file name length
            $local .= pack('v', 0);            // extra field length
            $local .= $nameBytes;
            $local .= $data;

            $localLen = strlen($local);

            // Central directory entry (signature 0x02014b50)
            $cd  = pack('V', 0x02014b50);  // signature
            $cd .= pack('v', 20);           // version made by
            $cd .= pack('v', 20);           // version needed
            $cd .= pack('v', 0);            // flags
            $cd .= pack('v', 0);            // compression
            $cd .= pack('V', $dosDate);     // mod time+date
            $cd .= pack('V', $crc);         // CRC-32
            $cd .= pack('V', $dataLen);     // compressed size
            $cd .= pack('V', $dataLen);     // uncompressed size
            $cd .= pack('v', $nameLen);     // file name length
            $cd .= pack('v', 0);            // extra field length
            $cd .= pack('v', 0);            // comment length
            $cd .= pack('v', 0);            // disk number start
            $cd .= pack('v', 0);            // internal attrs
            $cd .= pack('V', 0);            // external attrs
            $cd .= pack('V', $offset);      // relative offset of local header
            $cd .= $nameBytes;

            $localHeaders .= $local;
            $centralDir   .= $cd;
            $offset       += $localLen;
            $centralCount++;
        }

        $cdSize   = strlen($centralDir);
        $cdOffset = $offset;

        // End of central directory record (signature 0x06054b50)
        $eocd  = pack('V', 0x06054b50);    // signature
        $eocd .= pack('v', 0);              // disk number
        $eocd .= pack('v', 0);              // disk with central dir
        $eocd .= pack('v', $centralCount);  // entries on this disk
        $eocd .= pack('v', $centralCount);  // total entries
        $eocd .= pack('V', $cdSize);        // size of central dir
        $eocd .= pack('V', $cdOffset);      // offset of central dir
        $eocd .= pack('v', 0);              // comment length

        return $localHeaders . $centralDir . $eocd;
    }

    private function dosDateTime(): int
    {
        $t = getdate();
        $time = ($t['hours'] << 11) | ($t['minutes'] << 5) | ($t['seconds'] >> 1);
        $year = $t['year'] >= 1980 ? $t['year'] - 1980 : 0;
        $date = ($year << 9) | ($t['mon'] << 5) | $t['mday'];
        return ($date << 16) | $time;
    }
}
