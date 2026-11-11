<?php
declare(strict_types=1);

namespace App\Export\Decorator;

use App\Export\Contract\Exporter;
use App\Export\Model\ExportResult;

/**
 * DECORATOR: mã hóa AES-256-GCM (có xác thực toàn vẹn).
 * Định dạng output: [12 byte IV][16 byte TAG][ciphertext]
 */
final class EncryptingExporter extends ExporterDecorator
{
    private const CIPHER = 'aes-256-gcm';

    public function __construct(Exporter $inner, private readonly string $key)
    {
        if (strlen($key) !== 32) {
            throw new \InvalidArgumentException('Khóa AES-256 phải dài đúng 32 byte');
        }
        parent::__construct($inner);
    }

    protected function decorate(ExportResult $result): ExportResult
    {
        $iv     = random_bytes(12);
        $cipher = openssl_encrypt($result->content, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Mã hóa thất bại');
        }

        return $result->with(
            content: $iv . $tag . $cipher,
            mimeType: 'application/octet-stream',
            extension: $result->extension . '.enc',
            extraMetadata: ['encrypted' => self::CIPHER],
        );
    }

    public static function decrypt(string $payload, string $key): string
    {
        $plain = openssl_decrypt(
            substr($payload, 28), self::CIPHER, $key, OPENSSL_RAW_DATA,
            substr($payload, 0, 12), substr($payload, 12, 16),
        );
        return $plain !== false ? $plain : throw new \RuntimeException('Giải mã thất bại (sai khóa hoặc dữ liệu bị sửa)');
    }
}
