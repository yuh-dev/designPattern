<?php
declare(strict_types=1);

namespace App\Export\Exporter;

use App\Export\Contract\Exporter;
use App\Export\Model\ExportData;
use App\Export\Model\ExportResult;

/**
 * TEMPLATE METHOD + CHAIN OF RESPONSIBILITY
 *
 * export() là khung xử lý cố định: thử exec() -> lỗi thì chuyển cho $fallback
 * -> hết dự phòng thì ném RuntimeException. Lớp con chỉ cần cài exec().
 */
abstract class AbstractExporter implements Exporter
{
    public function __construct(
        protected readonly ?Exporter $fallback = null,
    ) {}

    final public function export(ExportData $data): ExportResult
    {
        try {
            return new ExportResult(
                $this->exec($data),
                $this->mimeType(),
                $this->extension(),
                ['format' => $this->extension(), 'exporter' => static::class],
            );
        } catch (\Throwable $e) {
            $name = (new \ReflectionClass($this))->getShortName();
            if ($this->fallback !== null) {
                fwrite(STDERR, "[Warning] $name thất bại: {$e->getMessage()} -> chuyển sang phương án dự phòng\n");
                return $this->fallback->export($data);
            }
            throw new \RuntimeException("Tất cả phương án xuất đều thất bại (cuối cùng: $name - {$e->getMessage()})", 0, $e);
        }
    }

    /** Bước thay đổi được: sinh nội dung file. */
    abstract protected function exec(ExportData $data): string;

    abstract protected function mimeType(): string;

    abstract protected function extension(): string;

    /** Tiện ích dùng chung cho lớp con. */
    protected function stringify(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            default         => (string) $value,
        };
    }
}
