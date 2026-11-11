<?php
declare(strict_types=1);

namespace App\Services\Export\Exporter;

use App\Services\Export\Model\ExportData;

/** STRATEGY: xuất JSON - mỗi dòng là một object {header: value}. */
final class JsonExporter extends AbstractExporter
{
    protected function exec(ExportData $data): string
    {
        $records = array_map(
            fn (array $row) => array_combine($data->getHeaders(), $row),
            $data->getRows(),
        );

        return json_encode(
            ['title' => $data->getTitle(), 'data' => $records],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    protected function mimeType(): string { return 'application/json'; }
    protected function extension(): string { return 'json'; }
}
