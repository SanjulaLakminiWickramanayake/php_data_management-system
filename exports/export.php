<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reports.php';
require_auth();
$key = input_string($_GET, 'module');
$format = input_string($_GET, 'format');
if (!in_array($format, ['pdf', 'excel', 'csv', 'print'], true)) {
    http_response_code(400);
    exit('Unsupported export format.');
}
$report = module_report($key);
$definition = $report['definition'];
$headers = $report['headers'];
$rows = $report['rows'];
$filename = $report['filename'];

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $output = fopen('php://output', 'wb');
    if ($output === false) {
        throw new RuntimeException('Unable to open output stream.');
    }
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, $headers, ',', '"', '');
    foreach ($rows as $row) {
        fputcsv($output, $row, ',', '"', '');
    }
    fclose($output);
    exit;
}

if ($format === 'excel') {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        http_response_code(503);
        exit('Excel export requires Composer dependencies. Run composer install.');
    }
    require_once $autoload;
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr($definition['title'], 0, 31));
    $sheet->fromArray($headers, null, 'A1');
    if ($rows) {
        $excelRows = $rows;
        foreach (array_keys($definition['columns']) as $fieldIndex => $field) {
            foreach ($excelRows as $rowIndex => $row) {
                if (in_array($field, ['amount', 'course_fee'], true)) {
                    $excelRows[$rowIndex][$fieldIndex] = (float) $row[$fieldIndex];
                } elseif (str_ends_with($field, '_date') || $field === 'registration_date') {
                    if (valid_date($row[$fieldIndex])) {
                        $excelRows[$rowIndex][$fieldIndex] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTimeImmutable($row[$fieldIndex]));
                    }
                }
            }
        }
        $sheet->fromArray($excelRows, null, 'A2');
    }
    $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
    $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '183B56']],
    ]);
    $sheet->freezePane('A2');
    foreach (range(1, count($headers)) as $column) {
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
    }
    foreach (['amount', 'course_fee'] as $moneyField) {
        $index = array_search($moneyField, array_keys($definition['columns']), true);
        if ($index !== false) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getStyle($column . '2:' . $column . max(2, count($rows) + 1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
        foreach (array_keys($definition['columns']) as $fieldIndex => $field) {
            if (str_ends_with($field, '_date') || $field === 'registration_date') {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($fieldIndex + 1);
                $sheet->getStyle($column . '2:' . $column . max(2, count($rows) + 1))->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            }
        }
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
}

if ($format === 'pdf') {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        http_response_code(503);
        exit('PDF export requires Composer dependencies. Run composer install.');
    }
    require_once $autoload;
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    echo report_pdf_bytes($report['html']);
    exit;
}

if ($format === 'print') {
    header('Content-Type: text/html; charset=UTF-8');
    echo report_html($definition['title'], $headers, $rows);
    echo '<script>window.addEventListener("load",()=>window.print())</script>';
    exit;
}
