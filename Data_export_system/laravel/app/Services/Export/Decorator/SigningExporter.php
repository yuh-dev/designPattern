<?php
declare(strict_types=1);

namespace App\Services\Export\Decorator;

use App\Services\Export\Contract\Exporter;
use App\Services\Export\Model\ExportResult;

/**
 * DECORATOR: ký số RSA-SHA256 lên nội dung cuối cùng.
 * Nội dung giữ nguyên; chữ ký (base64) được đặt vào metadata['signature']
 * và ExportService sẽ ghi ra file .sig đi kèm (detached signature).
 */
final class SigningExporter extends ExporterDecorator
{
    public function __construct(Exporter $inner, private readonly \OpenSSLAsymmetricKey|string $privateKey)
    {
        parent::__construct($inner);
    }

    protected function decorate(ExportResult $result): ExportResult
    {
        if (!openssl_sign($result->content, $signature, $this->privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Ký số thất bại: ' . openssl_error_string());
        }

        return $result->with(extraMetadata: [
            'signature'      => base64_encode($signature),
            'signature_algo' => 'RSA-SHA256',
            'sha256'         => hash('sha256', $result->content),
        ]);
    }

    public static function verify(string $content, string $signatureB64, \OpenSSLAsymmetricKey|string $publicKey): bool
    {
        return openssl_verify($content, base64_decode($signatureB64), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }
}
