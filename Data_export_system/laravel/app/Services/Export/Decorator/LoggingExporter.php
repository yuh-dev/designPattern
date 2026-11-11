<?php
declare(strict_types=1);

namespace App\Services\Export\Decorator;

use App\Services\Export\Contract\Exporter;
use App\Services\Export\Model\ExportData;
use App\Services\Export\Model\ExportResult;
use Psr\Log\LoggerInterface;

/** DECORATOR: ghi log thời gian, kích thước, lỗi (giống LoggingDistanceCalculator trong bài). */
final class LoggingExporter extends ExporterDecorator
{
    public function __construct(Exporter $inner, private readonly LoggerInterface $logger)
    {
        parent::__construct($inner);
    }

    public function export(ExportData $data): ExportResult
    {
        $start = hrtime(true);
        try {
            $result = parent::export($data);
            $this->logger->info('[Export] OK', [
                'title'     => $data->getTitle(),
                'rows'      => $data->countRows(),
                'extension' => $result->extension,
                'bytes'     => strlen($result->content),
                'ms'        => round((hrtime(true) - $start) / 1e6, 2),
            ]);
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('[Export] FAIL', ['title' => $data->getTitle(), 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function decorate(ExportResult $result): ExportResult
    {
        return $result;
    }
}
