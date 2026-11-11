<?php
declare(strict_types=1);

namespace App\Export\Decorator;

use App\Export\Contract\Exporter;
use App\Export\Model\ExportData;
use App\Export\Model\ExportResult;

/**
 * DECORATOR gốc: bọc một Exporter khác, cùng implement Exporter
 * nên có thể lồng nhiều lớp tùy ý: Sign(Encrypt(Compress(Pdf))).
 */
abstract class ExporterDecorator implements Exporter
{
    public function __construct(protected readonly Exporter $inner) {}

    public function export(ExportData $data): ExportResult
    {
        return $this->decorate($this->inner->export($data));
    }

    abstract protected function decorate(ExportResult $result): ExportResult;
}
