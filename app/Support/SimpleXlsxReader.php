<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Minimal .xlsx reader (no PhpSpreadsheet). Supports shared strings, inline values, and formula cached values.
 */
class SimpleXlsxReader
{
    /**
     * @return list<list<string|int|float|null>> One row = list of cell values in column order A..last used column
     */
    public static function readSheetByName(string $xlsxPath, string $sheetName): array
    {
        if (! is_file($xlsxPath)) {
            throw new RuntimeException("Excel file not found: {$xlsxPath}");
        }

        $zip = new ZipArchive;
        if ($zip->open($xlsxPath) !== true) {
            throw new RuntimeException("Could not open xlsx: {$xlsxPath}");
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $workbookRels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $workbookRels === false) {
            $zip->close();
            throw new RuntimeException('Invalid xlsx: missing workbook or rels.');
        }

        $sheetPath = self::resolveWorksheetPath($workbookXml, $workbookRels, $sheetName);
        if ($sheetPath === null) {
            $zip->close();
            throw new RuntimeException("Sheet not found: {$sheetName}");
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException("Could not read {$sheetPath}");
        }

        $strings = $sharedStrings !== false ? self::parseSharedStrings($sharedStrings) : [];

        return self::parseWorksheetData($sheetXml, $strings);
    }

    /**
     * @return list<string>
     */
    private static function parseSharedStrings(string $xml): array
    {
        $sx = new SimpleXMLElement($xml);
        $sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $out = [];
        foreach ($sx->si as $si) {
            $t = $si->t;
            if ($t !== null && (string) $t !== '') {
                $out[] = (string) $t;
                continue;
            }
            $buf = '';
            if (isset($si->r)) {
                foreach ($si->r as $r) {
                    if (isset($r->t)) {
                        $buf .= (string) $r->t;
                    }
                }
            }
            $out[] = $buf;
        }

        return $out;
    }

    /**
     * @param  list<string>  $strings
     * @return list<list<string|int|float|null>>
     */
    private static function parseWorksheetData(string $xml, array $strings): array
    {
        $sx = new SimpleXMLElement($xml);
        $sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = $sx->xpath('//m:sheetData/m:row') ?: [];

        $out = [];
        foreach ($rows as $row) {
            $rowIndex = (int) $row['r'];
            $byCol = [];
            $maxCol = 0;
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                if ($ref === '') {
                    continue;
                }
                $colIndex = self::columnLettersToIndex(self::splitCellRef($ref)['col']);
                $maxCol = max($maxCol, $colIndex);
                $t = (string) $c['t'];
                $v = $c->v;
                if ($t === 's' && $v !== null) {
                    $idx = (int) (string) $v;
                    $byCol[$colIndex] = $strings[$idx] ?? null;
                } elseif ($t === 'b' && $v !== null) {
                    $byCol[$colIndex] = ((string) $v) === '1';
                } elseif ($v !== null) {
                    $raw = (string) $v;
                    if (is_numeric($raw)) {
                        $byCol[$colIndex] = str_contains($raw, '.') || str_contains($raw, 'E') || str_contains($raw, 'e')
                            ? (float) $raw
                            : (int) $raw;
                    } else {
                        $byCol[$colIndex] = $raw;
                    }
                } else {
                    $byCol[$colIndex] = null;
                }
            }

            $line = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $line[] = $byCol[$i] ?? null;
            }
            $out[$rowIndex - 1] = $line;
        }

        ksort($out);

        return array_values($out);
    }

    /**
     * @return array{col: string, row: int}
     */
    private static function splitCellRef(string $ref): array
    {
        $col = preg_replace('/\d/', '', $ref) ?? '';
        $row = (int) preg_replace('/\D/', '', $ref);

        return ['col' => $col, 'row' => $row];
    }

    private static function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $n = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }

        return $n - 1;
    }

    private static function resolveWorksheetPath(string $workbookXml, string $relsXml, string $sheetName): ?string
    {
        $wb = new SimpleXMLElement($workbookXml);
        $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $nodes = $wb->xpath("//m:sheets/m:sheet[@name=".self::xpathLiteral($sheetName).']');
        if ($nodes === false || ! isset($nodes[0])) {
            return null;
        }
        $sheet = $nodes[0];
        $rid = (string) ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id ?? '');
        if ($rid === '') {
            return null;
        }

        $rels = new SimpleXMLElement($relsXml);
        foreach ($rels->Relationship as $rel) {
            if ((string) $rel['Id'] === $rid) {
                $target = (string) $rel['Target'];
                if (str_starts_with($target, '/')) {
                    return ltrim($target, '/');
                }

                return 'xl/'.$target;
            }
        }

        return null;
    }

    private static function xpathLiteral(string $s): string
    {
        if (! str_contains($s, "'")) {
            return "'".$s."'";
        }

        return 'concat('.implode(',', array_map(static fn ($p) => "'".$p."'", explode("'", $s))).')';
    }
}
