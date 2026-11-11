<?php
declare(strict_types=1);

namespace App\Export\Service;

use App\Export\Contract\Exporter;
use App\Export\Model\ExportData;
use App\Export\Model\ExportResult;

/**
 * CONTEXT của Strategy + Dependency Injection:
 * chỉ phụ thuộc interface Exporter, có thể đổi chiến lược lúc runtime.
 */
final class ExportService
{
    public function __construct(private Exporter $exporter) {}

    public function setExporter(Exporter $exporter): void
    {
        $this->exporter = $exporter;
    }

    public function export(ExportData $data): ExportResult
    {
        return $this->exporter->export($data);
    }

    /** Xuất và lưu file; trả về đường dẫn file chính. */
    public function exportToFile(ExportData $data, string $directory, string $baseName): string
    {
        $result = $this->export($data);

        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            throw new \RuntimeException("Không tạo được thư mục $directory");
        }

        $path = rtrim($directory, '/') . '/' . $baseName . '.' . $result->extension;
        file_put_contents($path, $result->content);

        if (isset($result->metadata['signature'])) {
            file_put_contents($path . '.sig', $result->metadata['signature']);
        }

        return $path;
    }
}
