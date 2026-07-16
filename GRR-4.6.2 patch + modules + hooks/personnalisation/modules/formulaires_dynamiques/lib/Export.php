<?php

class FormulairesDynamiquesExport
{
    public static function download($form, $fields, $responses, $format, $scope)
    {
        $format = self::normalizeFormat($format);
        $scope = $scope === 'response' ? 'response' : 'all';
        $title = isset($form['titre']) && trim((string) $form['titre']) !== '' ? (string) $form['titre'] : 'formulaire';
        $baseName = self::filename($title, $scope);
        $rows = self::rows($fields, $responses);

        if ($format === 'xlsx') {
            self::sendXlsx($baseName.'.xlsx', $rows);
        } elseif ($format === 'pdf') {
            self::sendPdf($baseName.'.pdf', $title, $rows);
        } else {
            self::sendCsv($baseName.'.csv', $rows);
        }
    }

    public static function normalizeFormat($format)
    {
        $format = strtolower(trim((string) $format));
        return in_array($format, array('csv', 'xlsx', 'pdf'), true) ? $format : 'csv';
    }

    private static function rows($fields, $responses)
    {
        $header = array('Reference', 'Date', 'Source', 'Declarant');
        foreach ((array) $fields as $field) {
            $header[] = isset($field['libelle']) ? (string) $field['libelle'] : '';
        }

        $rows = array($header);
        foreach ((array) $responses as $response) {
            $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
            $row = array(
                '#'.(int) (isset($response['id']) ? $response['id'] : 0),
                self::formatDate(isset($response['created_at']) ? (int) $response['created_at'] : 0),
                self::sourceLabel(isset($response['source']) ? $response['source'] : ''),
                self::submitterLabel($response),
            );

            foreach ((array) $fields as $field) {
                $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
                $row[] = isset($values[$fieldId]) ? (string) $values[$fieldId] : '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private static function sendCsv($filename, $rows)
    {
        self::headers('text/csv; charset=utf-8', $filename);
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        foreach ($rows as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }

    private static function sendXlsx($filename, $rows)
    {
        if (!class_exists('ZipArchive')) {
            self::headers('text/plain; charset=utf-8', 'export-xlsx-indisponible.txt');
            echo "Export XLSX indisponible : l extension PHP ZipArchive n est pas active.";
            exit;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'formdyn_xlsx_');
        $zip = new ZipArchive();
        if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            if ($tmp !== false) {
                @unlink($tmp);
            }
            self::headers('text/plain; charset=utf-8', 'export-xlsx-erreur.txt');
            echo "Export XLSX impossible : creation du fichier temporaire echouee.";
            exit;
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::relsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($rows));
        $zip->close();

        self::headers('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $filename);
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    private static function sendPdf($filename, $title, $rows)
    {
        self::headers('application/pdf', $filename);
        echo self::pdf($title, $rows);
        exit;
    }

    private static function headers($contentType, $filename)
    {
        if (!headers_sent()) {
            header('Content-Type: '.$contentType);
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }
    }

    private static function sheetXml($rows)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $r = $rowIndex + 1;
            $xml .= '<row r="'.$r.'">';
            foreach ($row as $colIndex => $value) {
                $cell = self::columnName($colIndex + 1).$r;
                $xml .= '<c r="'.$cell.'" t="inlineStr"><is><t xml:space="preserve">'.self::xml($value).'</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function contentTypesXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function relsXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Reponses" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRelsXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function stylesXml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            .'</styleSheet>';
    }

    private static function pdf($title, $rows)
    {
        $lines = array('Formulaire : '.$title, 'Export genere le '.date('d/m/Y H:i'), '');
        if (count($rows) > 0) {
            $header = $rows[0];
            for ($i = 1; $i < count($rows); $i++) {
                $lines[] = 'Reponse '.$rows[$i][0];
                for ($j = 1; $j < count($header); $j++) {
                    $lines[] = $header[$j].' : '.(isset($rows[$i][$j]) ? $rows[$i][$j] : '');
                }
                $lines[] = '';
            }
        }

        $wrapped = array();
        foreach ($lines as $line) {
            foreach (self::wrapLine($line, 95) as $part) {
                $wrapped[] = $part;
            }
        }

        $pages = array_chunk($wrapped, 54);
        if (count($pages) === 0) {
            $pages = array(array('Aucune donnee.'));
        }

        $objects = array();
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $kids = array();
        foreach ($pages as $index => $pageLines) {
            $pageId = 4 + ($index * 2);
            $contentId = $pageId + 1;
            $kids[] = $pageId.' 0 R';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $stream = self::pdfStream($pageLines);
            $objects[$contentId] = '<< /Length '.strlen($stream).' >>'."\nstream\n".$stream."\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = array(0);
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $max = max(array_keys($objects));
        $pdf .= "xref\n0 ".($max + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= sprintf('%010d 00000 n ', isset($offsets[$i]) ? $offsets[$i] : 0)."\n";
        }
        $pdf .= "trailer\n<< /Size ".($max + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    private static function pdfStream($lines)
    {
        $stream = "BT\n/F1 10 Tf\n40 805 Td\n13 TL\n";
        foreach ($lines as $line) {
            $stream .= '('.self::pdfText($line).") Tj\nT*\n";
        }
        return $stream."ET";
    }

    private static function wrapLine($line, $length)
    {
        $line = trim(preg_replace('/\s+/', ' ', (string) $line));
        if ($line === '') {
            return array('');
        }

        return explode("\n", wordwrap($line, $length, "\n", true));
    }

    private static function pdfText($value)
    {
        $value = (string) $value;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        return str_replace(array('\\', '(', ')'), array('\\\\', '\(', '\)'), $value);
    }

    private static function columnName($index)
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = (int) floor($index / 26);
        }

        return $name;
    }

    private static function filename($title, $scope)
    {
        $name = strtolower(trim((string) $title));
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $name);
            if ($converted !== false) {
                $name = $converted;
            }
        }
        $name = preg_replace('/[^a-z0-9]+/', '-', $name);
        $name = trim($name, '-');
        if ($name === '') {
            $name = 'formulaire';
        }

        return 'formdyn-'.$name.'-'.$scope.'-'.date('Ymd-His');
    }

    private static function xml($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private static function sourceLabel($source)
    {
        $source = (string) $source;
        if ($source === 'autonomous') {
            return 'Autonome';
        }
        if ($source === 'grr') {
            return 'Integre GRR';
        }

        return $source !== '' ? $source : '-';
    }

    private static function submitterLabel($response)
    {
        $name = isset($response['submitter_name']) ? trim((string) $response['submitter_name']) : '';
        $login = isset($response['submitter_login']) ? trim((string) $response['submitter_login']) : '';
        $email = isset($response['submitter_email']) ? trim((string) $response['submitter_email']) : '';

        if ($name !== '' && $login !== '') {
            return $name.' ('.$login.')';
        }
        if ($name !== '') {
            return $name;
        }
        if ($login !== '') {
            return $login;
        }
        if ($email !== '') {
            return $email;
        }

        return 'Anonyme';
    }

    private static function formatDate($timestamp)
    {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 ? date('d/m/Y H:i', $timestamp) : '';
    }
}
