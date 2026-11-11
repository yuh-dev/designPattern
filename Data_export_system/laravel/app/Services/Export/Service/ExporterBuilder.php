<?php
declare(strict_types=1);

namespace App\Services\Export\Service;

use App\Services\Export\Contract\Exporter;
use App\Services\Export\Decorator\CompressingExporter;
use App\Services\Export\Decorator\EncryptingExporter;
use App\Services\Export\Decorator\LoggingExporter;
use App\Services\Export\Decorator\SigningExporter;
use App\Services\Export\Exporter\CsvExporter;
use App\Services\Export\Exporter\ExcelExporter;
use App\Services\Export\Exporter\JsonExporter;
use App\Services\Export\Exporter\PdfExporter;
use Psr\Log\LoggerInterface;

/**
 * Lắp ráp Exporter từ cấu hình - giấu chi tiết lồng ghép khỏi client
 * (được gọi từ ExportServiceProvider, giống bài viết bind DistanceCalculator).
 *
 * Thứ tự decorator cố định & đúng nghiệp vụ:
 *   Log( Sign( Encrypt( Compress( Chain[format -> fallback...] ) ) ) )
 *   - Nén trước khi mã hóa (dữ liệu đã mã hóa không nén được).
 *   - Ký sau cùng để chữ ký phủ đúng file người nhận tải về.
 */
final class ExporterBuilder
{
    /** @var string[] */
    private array $formats = [];
    private bool $compress = false;
    private ?string $encryptionKey = null;
    private \OpenSSLAsymmetricKey|string|null $signingKey = null;
    private ?LoggerInterface $logger = null;
    /** @var array<string, int> */
    private array $limits = [];

    public static function create(): self { return new self(); }

    /** Định dạng chính (Strategy). */
    public function format(string $format): self
    {
        $this->formats = [strtolower($format)];
        return $this;
    }

    /** Các định dạng dự phòng theo thứ tự (Chain of Responsibility). */
    public function fallbackTo(string ...$formats): self
    {
        array_push($this->formats, ...array_map('strtolower', $formats));
        return $this;
    }

    /** Giới hạn số dòng cho định dạng (để cấu hình/kiểm thử fallback). */
    public function maxRows(string $format, int $rows): self
    {
        $this->limits[strtolower($format)] = $rows;
        return $this;
    }

    public function compress(): self { $this->compress = true; return $this; }
    public function encrypt(string $key): self { $this->encryptionKey = $key; return $this; }
    public function sign(\OpenSSLAsymmetricKey|string $privateKey): self { $this->signingKey = $privateKey; return $this; }
    public function withLogging(LoggerInterface $logger): self { $this->logger = $logger; return $this; }

    public function build(): Exporter
    {
        if ($this->formats === []) {
            throw new \LogicException('Chưa chọn định dạng xuất');
        }

        // Dựng chuỗi từ cuối lên đầu: mắt xích sau là fallback của mắt xích trước
        $exporter = null;
        foreach (array_reverse(array_unique($this->formats)) as $format) {
            $exporter = $this->makeFormat($format, $exporter);
        }

        if ($this->compress)             { $exporter = new CompressingExporter($exporter); }
        if ($this->encryptionKey !== null) { $exporter = new EncryptingExporter($exporter, $this->encryptionKey); }
        if ($this->signingKey !== null)  { $exporter = new SigningExporter($exporter, $this->signingKey); }
        if ($this->logger !== null)      { $exporter = new LoggingExporter($exporter, $this->logger); }

        return $exporter;
    }

    private function makeFormat(string $format, ?Exporter $fallback): Exporter
    {
        $limit = $this->limits[$format] ?? null;

        return match ($format) {
            'csv'                 => new CsvExporter($fallback),
            'json'                => new JsonExporter($fallback),
            'xls', 'excel'        => $limit ? new ExcelExporter($fallback, $limit) : new ExcelExporter($fallback),
            'pdf'                 => $limit ? new PdfExporter($fallback, $limit) : new PdfExporter($fallback),
            default               => throw new \InvalidArgumentException("Định dạng không hỗ trợ: $format"),
        };
    }
}
