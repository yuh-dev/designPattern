<?php
declare(strict_types=1);

namespace App\Services\Export\Exporter;

use App\Services\Export\Model\ExportData;

/** STRATEGY: xuất CSV (UTF-8 có BOM để Excel đọc đúng tiếng Việt). */
final class CsvExporter extends AbstractExporter
{
    protected function exec(ExportData $data): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $data->getHeaders(), ',', '"', '');
        foreach ($data->getRows() as $row) {
            fputcsv($stream, array_map($this->stringify(...), $row), ',', '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return "\xEF\xBB\xBF" . $csv;
    }

    protected function mimeType(): string { return 'text/csv'; }
    protected function extension(): string { return 'csv'; }
}
