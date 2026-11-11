<?php

return [
    // STRATEGY mặc định khi không chỉ định định dạng
    'default' => env('EXPORT_DEFAULT_FORMAT', 'csv'),

    // CHAIN OF RESPONSIBILITY: định dạng chính => danh sách dự phòng theo thứ tự
    'fallbacks' => [
        'pdf'  => ['xls', 'csv'],
        'xls'  => ['csv'],
        'json' => ['csv'],
        'csv'  => [],
    ],

    // Giới hạn số dòng của từng định dạng (vượt quá => chuyển sang dự phòng)
    'max_rows' => [
        'pdf' => (int) env('EXPORT_PDF_MAX_ROWS', 1000),
        'xls' => (int) env('EXPORT_XLS_MAX_ROWS', 65535),
    ],

    // DECORATOR bật mặc định (có thể ghi đè theo request / tham số lệnh)
    'decorators' => [
        'compress' => (bool) env('EXPORT_COMPRESS', false),
        'encrypt'  => (bool) env('EXPORT_ENCRYPT', false),
        'sign'     => (bool) env('EXPORT_SIGN', false),
        'log'      => true,
    ],

    // Nơi lưu file xuất & khóa
    'disk'      => env('EXPORT_DISK', 'local'),
    'directory' => 'exports',

    'keys' => [
        'encryption'  => env('EXPORT_ENCRYPTION_KEY'),
        'private_key' => 'keys/export_private.pem',
        'public_key'  => 'keys/export_public.pem',
    ],
];
