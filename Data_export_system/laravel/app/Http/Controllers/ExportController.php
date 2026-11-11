<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Export\Model\ExportData;
use App\Services\Export\Service\ExporterFactory;
use App\Services\Export\Service\ExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function __construct(
        private readonly ExportService $service,
        private readonly ExporterFactory $factory,
    ) {}

    /** GET /exports/users.{format}?compress=1&encrypt=1&sign=1 */
    public function users(Request $request, string $format): Response
    {
        $options = $request->validate([
            'compress' => 'sometimes|boolean',
            'encrypt'  => 'sometimes|boolean',
            'sign'     => 'sometimes|boolean',
        ]);

        $data = ExportData::fromIterable('Danh sách người dùng', User::query()->orderBy('id')->cursor(), [
            'id'         => 'ID',
            'name'       => 'Họ tên',
            'email'      => 'Email',
            'created_at' => 'Ngày tạo',
        ]);

        return $this->service
            ->using($this->factory->make($format, array_map('boolval', $options)))
            ->download($data, 'users');
    }
}
