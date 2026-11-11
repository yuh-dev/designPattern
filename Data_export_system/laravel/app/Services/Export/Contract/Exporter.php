<?php
declare(strict_types=1);

namespace App\Services\Export\Contract;

use App\Services\Export\Model\ExportData;
use App\Services\Export\Model\ExportResult;

/**
 * Abstraction trung tâm (tương đương DistanceCalculator trong bài viết).
 * Mọi Strategy, mắt xích Chain và Decorator đều tuân theo interface này.
 */
interface Exporter
{
    public function export(ExportData $data): ExportResult;
}
