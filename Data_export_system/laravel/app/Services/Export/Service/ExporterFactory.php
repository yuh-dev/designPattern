<?php
declare(strict_types=1);

namespace App\Services\Export\Service;

use App\Services\Export\Contract\Exporter;
use Psr\Log\LoggerInterface;

/**
 * Đọc config/export.php và lắp Exporter qua ExporterBuilder.
 * Cho phép ghi đè từng phần lúc runtime (từ request / tham số artisan).
 */
final class ExporterFactory
{
    /** @param array<string, mixed> $config nội dung config('export') */
    public function __construct(
        private readonly array $config,
        private readonly KeyStore $keys,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array{fallbacks?: string[], compress?: bool, encrypt?: bool, sign?: bool} $overrides
     */
    public function make(?string $format = null, array $overrides = []): Exporter
    {
        $format    = $format ?? $this->config['default'];
        $fallbacks = $overrides['fallbacks'] ?? ($this->config['fallbacks'][$format] ?? []);
        $opt       = fn (string $k) => (bool) ($overrides[$k] ?? $this->config['decorators'][$k] ?? false);

        $builder = ExporterBuilder::create()->format($format)->fallbackTo(...$fallbacks);
        foreach ($this->config['max_rows'] ?? [] as $fmt => $rows) {
            $builder->maxRows($fmt, (int) $rows);
        }

        if ($opt('compress')) { $builder->compress(); }
        if ($opt('encrypt'))  { $builder->encrypt($this->keys->encryptionKey()); }
        if ($opt('sign'))     { $builder->sign($this->keys->privateKey()); }
        if ($this->config['decorators']['log'] ?? true) { $builder->withLogging($this->logger); }

        return $builder->build();
    }

    /** @return string[] */
    public function supportedFormats(): array
    {
        return ['csv', 'json', 'xls', 'pdf'];
    }
}
