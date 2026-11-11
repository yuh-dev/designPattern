<?php
declare(strict_types=1);

namespace App\Export\Decorator;

use App\Export\Contract\Exporter;
use App\Export\Model\ExportResult;

/** DECORATOR: nén gzip -> report.csv thành report.csv.gz */
final class CompressingExporter extends ExporterDecorator
{
    public function __construct(Exporter $inner, private readonly int $level = 9)
    {
        parent::__construct($inner);
    }

    protected function decorate(ExportResult $result): ExportResult
    {
        $gz = gzencode($result->content, $this->level);
        if ($gz === false) {
            throw new \RuntimeException('Nén gzip thất bại');
        }

        return $result->with(
            content: $gz,
            mimeType: 'application/gzip',
            extension: $result->extension . '.gz',
            extraMetadata: [
                'compressed'    => 'gzip',
                'original_size' => strlen($result->content),
                'compressed_size' => strlen($gz),
            ],
        );
    }

    public static function decompress(string $content): string
    {
        return gzdecode($content) ?: throw new \RuntimeException('Giải nén thất bại');
    }
}
