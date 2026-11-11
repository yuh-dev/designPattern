<?php

namespace App\Console\Commands;

use App\Services\Export\Service\KeyStore;
use Illuminate\Console\Command;

class ExportKeygenCommand extends Command
{
    protected $signature = 'export:keygen {--force : Ghi đè khóa đã có}';

    protected $description = 'Sinh khóa AES-256 (mã hóa) và cặp khóa RSA (ký số) cho hệ thống xuất dữ liệu';

    public function handle(KeyStore $keys): int
    {
        if ($keys->hasSigningKeys() && !$this->option('force')) {
            $this->warn('Khóa đã tồn tại. Dùng --force để tạo lại.');
            return self::FAILURE;
        }

        $aesKey  = $keys->generate();
        $envPath = $this->laravel->environmentFilePath();
        $env     = file_get_contents($envPath);
        $line    = "EXPORT_ENCRYPTION_KEY=$aesKey";
        $env     = preg_match('/^EXPORT_ENCRYPTION_KEY=.*$/m', $env)
            ? preg_replace('/^EXPORT_ENCRYPTION_KEY=.*$/m', $line, $env)
            : rtrim($env) . "\n\n$line\n";
        file_put_contents($envPath, $env);

        $this->info('Đã tạo cặp khóa RSA trong storage và ghi EXPORT_ENCRYPTION_KEY vào .env');
        return self::SUCCESS;
    }
}
