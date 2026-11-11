# Hệ thống xuất dữ liệu (Data Export System)

Bài tập mở rộng từ bài viết *"Design Patterns trong PHP – Từng bước giải bài toán tính khoảng cách"* (Viblo),
phần **Áp dụng vào các bài toán khác → Hệ thống xuất dữ liệu**:

| Pattern | Áp dụng | Tương đương trong bài viết |
|---|---|---|
| **Strategy** | `CsvExporter`, `JsonExporter`, `ExcelExporter`, `PdfExporter` cùng implement `Exporter` | `DistanceByCar/Bike/BirdFlyCalculator` |
| **Chain of Responsibility** | Mỗi exporter nhận `?Exporter $fallback`; lỗi → chuyển cho mắt xích sau (PDF → Excel → CSV) | fallback Car → Bike → BirdFly |
| **Template Method** | `AbstractExporter::export()` cố định luồng *thử → fallback → ném lỗi*, lớp con chỉ cài `exec()` | `AbstractDistanceCalculator` |
| **Decorator** | `CompressingExporter` (gzip), `EncryptingExporter` (AES-256-GCM), `SigningExporter` (RSA-SHA256), `LoggingExporter` | `CachingDistanceCalculator`, `LoggingDistanceCalculator` |
| **Dependency Injection** | `ExportServiceProvider` bind `Exporter`/`ExportService`; command & controller chỉ type-hint abstraction | `DistanceCalculatorServiceProvider` |

Thứ tự decorator: `Log(Sign(Encrypt(Compress(Chain))))`: nén trước mã hóa (dữ liệu đã mã hóa không nén được), ký sau cùng để chữ ký phủ đúng file người nhận tải về.

## Cấu trúc

```
plain-php/            # Bản PHP thuần, không framework (php plain-php/demo.php)
laravel/              # Bản tích hợp Laravel 13
  app/Services/Export/
    Contract/Exporter.php
    Model/            ExportData (value object), ExportResult
    Exporter/         AbstractExporter + 4 strategy
    Decorator/        ExporterDecorator + Compress/Encrypt/Sign/Logging
    Service/          ExporterBuilder, ExporterFactory, ExportService, KeyStore
  app/Providers/ExportServiceProvider.php
  app/Console/Commands/ExportUsersCommand.php, ExportKeygenCommand.php
  app/Http/Controllers/ExportController.php
  config/export.php   # định dạng mặc định, chuỗi fallback, giới hạn dòng, decorator
  tests/Feature/Export/
```

## Chạy bản Laravel

```bash
cd laravel
php artisan migrate:fresh --seed     # 1501 user (vượt giới hạn PDF 1000 dòng để thấy fallback)
php artisan export:keygen            # sinh khóa AES (.env) + cặp khóa RSA (storage/app/private/keys)

php artisan export:users csv
php artisan export:users pdf                          # PDF quá giới hạn -> tự chuyển sang Excel
php artisan export:users pdf --fallback=json --compress --encrypt --sign

php artisan serve
# http://localhost:8000/exports/users.csv
# http://localhost:8000/exports/users.pdf
# http://localhost:8000/exports/users.json?compress=1&encrypt=1&sign=1   (chữ ký trong header X-Export-Signature)

php artisan test
```

File xuất lưu ở `laravel/storage/app/private/exports/`, log ở `laravel/storage/logs/laravel.log`.

## Thêm định dạng mới (Open/Closed)

1. Tạo `XmlExporter extends AbstractExporter`, cài `exec()`, `mimeType()`, `extension()`.
2. Thêm một dòng `'xml' => new XmlExporter($fallback)` trong `ExporterBuilder::makeFormat()` và khai báo fallback trong `config/export.php`.

Không phải sửa decorator, service, command hay controller.
