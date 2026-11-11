<?php
declare(strict_types=1);

namespace App\Export\Contract;

use App\Export\Model\ExportData;
use App\Export\Model\ExportResult;

/**
 * Abstraction trung tâm (tương đương DistanceCalculator trong bài viết).
 * Mọi Strategy, mắt xích Chain và Decorator đều tuân theo interface này.
 */
interface Exporter
{
    public function export(ExportData $data): ExportResult;
}
