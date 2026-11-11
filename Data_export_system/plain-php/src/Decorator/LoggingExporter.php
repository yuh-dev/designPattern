<?php
declare(strict_types=1);

namespace App\Export\Decorator;

use App\Export\Contract\Exporter;
use App\Export\Model\ExportData;
use App\Export\Model\ExportResult;

/** DECORATOR: ghi log thời gian, kích thước, lỗi (giống LoggingDistanceCalculator trong bài). */
final class LoggingExporter extends ExporterDecorator
{
    /** @param \Closure(string):void|null $logger */
    public function __construct(Exporter $inner, private readonly ?\Closure $logger = null)
    {
        parent::__construct($inner);
    }

    public function export(ExportData $data): ExportResult
    {
        $start = hrtime(true);
        try {
            $result = parent::export($data);
            $this->log(sprintf(
                'OK "%s": %d dòng -> .%s, %s bytes, %.2f ms',
                $data->getTitle(), $data->countRows(), $result->extension,
                number_format(strlen($result->content)), (hrtime(true) - $start) / 1e6,
            ));
            return $result;
        } catch (\Throwable $e) {
            $this->log("FAIL \"{$data->getTitle()}\": {$e->getMessage()}");
            throw $e;
        }
    }

    protected function decorate(ExportResult $result): ExportResult
    {
        return $result;
    }

    private function log(string $message): void
    {
        $line = '[' . date('H:i:s') . '] [ExportLog] ' . $message;
        $this->logger ? ($this->logger)($line) : print($line . "\n");
    }
}
