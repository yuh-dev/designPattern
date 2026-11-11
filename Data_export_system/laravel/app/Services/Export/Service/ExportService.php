<?php
declare(strict_types=1);

namespace App\Services\Export\Service;

use App\Services\Export\Contract\Exporter;
use App\Services\Export\Model\ExportData;
use App\Services\Export\Model\ExportResult;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

/**
 * CONTEXT của Strategy + Dependency Injection:
 * chỉ phụ thuộc interface Exporter (Laravel container inject),
 * có thể đổi chiến lược lúc runtime.
 */
final class ExportService
{
    public function __construct(
        private Exporter $exporter,
        private readonly Filesystem $disk,
    ) {}

    public function using(Exporter $exporter): self
    {
        $clone = clone $this;
        $clone->exporter = $exporter;
        return $clone;
    }

    public function export(ExportData $data): ExportResult
    {
        return $this->exporter->export($data);
    }

    /** Xuất và lưu vào disk; trả về đường dẫn tương đối của file chính. */
    public function store(ExportData $data, string $baseName, string $directory = 'exports'): string
    {
        $result = $this->export($data);
        $path   = trim($directory, '/') . '/' . $baseName . '.' . $result->extension;

        $this->disk->put($path, $result->content);
        if (isset($result->metadata['signature'])) {
            $this->disk->put($path . '.sig', $result->metadata['signature']);
        }

        return $path;
    }

    /** Xuất và trả về HTTP response tải file (chữ ký nằm trong header). */
    public function download(ExportData $data, string $baseName): Response
    {
        $result   = $this->export($data);
        $filename = $baseName . '.' . $result->extension;

        $headers = [
            'Content-Type'        => $result->mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Export-Format'     => $result->metadata['format'] ?? $result->extension,
        ];
        if (isset($result->metadata['signature'])) {
            $headers['X-Export-Signature'] = $result->metadata['signature'];
        }

        return new Response($result->content, 200, $headers);
    }
}
