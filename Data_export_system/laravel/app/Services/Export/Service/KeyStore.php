<?php
declare(strict_types=1);

namespace App\Services\Export\Service;

use Illuminate\Contracts\Filesystem\Filesystem;

/** Quản lý khóa AES (từ .env) và cặp khóa RSA (lưu trên disk private). */
final class KeyStore
{
    public function __construct(
        private readonly ?string $encryptionKey,
        private readonly Filesystem $disk,
        private readonly string $privateKeyPath,
        private readonly string $publicKeyPath,
    ) {}

    public function encryptionKey(): string
    {
        $raw = (string) $this->encryptionKey;
        $key = str_starts_with($raw, 'base64:') ? base64_decode(substr($raw, 7), true) : $raw;

        if ($key === false || strlen($key) !== 32) {
            throw new \RuntimeException('EXPORT_ENCRYPTION_KEY chưa cấu hình hoặc không đủ 32 byte. Chạy: php artisan export:keygen');
        }
        return $key;
    }

    public function privateKey(): \OpenSSLAsymmetricKey
    {
        return openssl_pkey_get_private($this->read($this->privateKeyPath))
            ?: throw new \RuntimeException('Private key không hợp lệ');
    }

    public function publicKey(): string
    {
        return $this->read($this->publicKeyPath);
    }

    public function hasSigningKeys(): bool
    {
        return $this->disk->exists($this->privateKeyPath) && $this->disk->exists($this->publicKeyPath);
    }

    /** Sinh cặp khóa RSA-2048 mới; trả về khóa AES ngẫu nhiên dạng base64:... */
    public function generate(): string
    {
        $rsa = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($rsa, $privatePem);
        $this->disk->put($this->privateKeyPath, $privatePem);
        $this->disk->put($this->publicKeyPath, openssl_pkey_get_details($rsa)['key']);

        return 'base64:' . base64_encode(random_bytes(32));
    }

    private function read(string $path): string
    {
        if (!$this->disk->exists($path)) {
            throw new \RuntimeException("Không thấy khóa ký số tại '$path'. Chạy: php artisan export:keygen");
        }
        return $this->disk->get($path);
    }
}
