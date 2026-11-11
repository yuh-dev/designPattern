<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Export\Model\ExportData;
use App\Services\Export\Service\ExporterFactory;
use App\Services\Export\Service\ExportService;
use Illuminate\Console\Command;

/**
 * Tương đương GetDistanceCommand trong bài: chỉ phụ thuộc abstraction,
 * mọi lựa chọn pattern đến từ tham số / config.
 */
class ExportUsersCommand extends Command
{
    protected $signature = 'export:users
        {format? : csv|json|xls|pdf (mặc định theo config)}
        {--fallback=* : Định dạng dự phòng, VD --fallback=xls --fallback=csv}
        {--compress : Nén gzip}
        {--encrypt : Mã hóa AES-256-GCM}
        {--sign : Ký số RSA-SHA256}';

    protected $description = 'Xuất danh sách người dùng ra file trong storage/app/private/exports';

    public function handle(ExportService $service, ExporterFactory $factory): int
    {
        $format = $this->argument('format');
        if ($format !== null && !in_array($format, $factory->supportedFormats(), true)) {
            $this->error("Định dạng không hỗ trợ: $format");
            return self::INVALID;
        }

        $overrides = array_filter([
            'fallbacks' => $this->option('fallback') ?: null,
            'compress'  => $this->option('compress') ?: null,
            'encrypt'   => $this->option('encrypt') ?: null,
            'sign'      => $this->option('sign') ?: null,
        ], fn ($v) => $v !== null);

        $data = ExportData::fromIterable('Danh sách người dùng', User::query()->orderBy('id')->cursor(), [
            'id'         => 'ID',
            'name'       => 'Họ tên',
            'email'      => 'Email',
            'created_at' => 'Ngày tạo',
        ]);

        try {
            $path = $service
                ->using($factory->make($format, $overrides))
                ->store($data, 'users_' . now()->format('Ymd_His'), config('export.directory'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Đã xuất {$data->countRows()} dòng -> " . storage_path("app/private/$path"));
        return self::SUCCESS;
    }
}
