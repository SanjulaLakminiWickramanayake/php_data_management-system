<?php
declare(strict_types=1);

require_once __DIR__ . '/modules.php';

function module_report(string $key, ?array $input = null): array
{
    $definition = module_definition($key);
    $data = module_rows($key, false, $input)['rows'];
    $headers = array_values($definition['columns']);
    $rows = [];
    foreach ($data as $record) {
        $values = [];
        foreach (array_keys($definition['columns']) as $field) {
            $value = $record[$field] ?? '';
            if ($field === 'amount' || $field === 'course_fee') {
                $value = number_format((float) $value, 2, '.', '');
            }
            $values[] = (string) $value;
        }
        $rows[] = $values;
    }

    return [
        'definition' => $definition,
        'headers' => $headers,
        'rows' => $rows,
        'filename' => $key . '_' . date('Y-m-d'),
        'html' => report_html($definition['title'], $headers, $rows),
    ];
}

function report_pdf_bytes(string $html): string
{
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $canvas = $dompdf->getCanvas();
    $canvas->page_text(730, 570, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 8, [0.35, 0.4, 0.45]);
    return $dompdf->output();
}

function report_html(string $title, array $headers, array $rows): string
{
    $html = '<!doctype html><html><head><meta charset="UTF-8"><style>
        @page{margin:28px 26px}body{font-family:DejaVu Sans,sans-serif;color:#203040;font-size:10px}
        h1{font-size:20px;margin:0 0 5px}p{color:#607080;margin:0 0 18px}
        table{width:100%;border-collapse:collapse}thead{display:table-header-group}
        th{background:#183b56;color:#fff;text-align:left}th,td{border:1px solid #dce3e8;padding:7px;vertical-align:top}
        tr:nth-child(even){background:#f4f7f9}</style></head><body>';
    $html .= '<h1>' . e($title) . ' report</h1><p>Generated ' . e(date('F j, Y H:i')) . ' · ' . count($rows) . ' records</p><table><thead><tr>';
    foreach ($headers as $header) {
        $html .= '<th>' . e($header) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= '<td>' . e($value) . '</td>';
        }
        $html .= '</tr>';
    }
    return $html . '</tbody></table></body></html>';
}
