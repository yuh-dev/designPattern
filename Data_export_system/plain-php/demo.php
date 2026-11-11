<?php
declare(strict_types=1);

require __DIR__ . '/autoload.php';

use App\Export\Decorator\CompressingExporter;
use App\Export\Decorator\EncryptingExporter;
use App\Export\Decorator\SigningExporter;
use App\Export\Exporter\CsvExporter;
use App\Export\Exporter\ExcelExporter;
use App\Export\Exporter\JsonExporter;
use App\Export\Exporter\PdfExporter;
use App\Export\Model\ExportData;
use App\Export\Service\ExporterBuilder;
use App\Export\Service\ExportService;

$out = __DIR__ . '/output';

$data = new ExportData(
    'Báo cáo doanh thu Q3',
    ['Mã', 'Khách hàng', 'Thành phố', 'Doanh thu (VNĐ)', 'Đã thanh toán'],
    [
        [1, 'Nguyễn Văn An',  'Hà Nội',  15_500_000, true],
        [2, 'Trần Thị Bình',  'Đà Nẵng',  8_200_000, false],
        [3, 'Lê Hoàng Cường', 'TP.HCM',  23_750_000, true],
        [4, 'Phạm "Dũng"',    'Huế',      4_100_000, true],
    ],
);

// Dữ liệu lớn để kích hoạt fallback
$bigData = new ExportData(
    'Nhật ký giao dịch',
    ['ID', 'Mô tả', 'Số tiền'],
    array_map(fn ($i) => [$i, "Giao dịch #$i", $i * 1000], range(1, 5_000)),
);

echo "=== 1. STRATEGY: đổi định dạng lúc runtime ===\n";
$service = new ExportService(new CsvExporter());
foreach ([new CsvExporter(), new JsonExporter(), new ExcelExporter(), new PdfExporter()] as $strategy) {
    $service->setExporter($strategy);
    echo '  -> ', $service->exportToFile($data, $out, 'doanh_thu'), "\n";
}

echo "\n=== 2. CHAIN OF RESPONSIBILITY: PDF -> Excel -> CSV ===\n";
// PDF tối đa 1000 dòng, Excel (giả lập) tối đa 2000 dòng => rơi xuống CSV
$chain = new PdfExporter(
    new ExcelExporter(
        new CsvExporter(),
        maxRows: 2_000,
    ),
);
$result = $chain->export($bigData);
echo "  Kết quả cuối: định dạng .{$result->extension} do {$result->metadata['exporter']} tạo\n";

echo "\n=== 3. DECORATOR: nén + mã hóa + ký số (+ log) ===\n";
$aesKey     = random_bytes(32);
$rsa        = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$publicKey  = openssl_pkey_get_details($rsa)['key'];

$secureExporter = ExporterBuilder::create()
    ->format('pdf')->fallbackTo('xls', 'csv')
    ->maxRows('xls', 2_000)
    ->compress()
    ->encrypt($aesKey)
    ->sign($rsa)
    ->withLogging()
    ->build();

$service->setExporter($secureExporter);
$path = $service->exportToFile($bigData, $out, 'giao_dich_bao_mat');
echo "  -> $path (+ .sig)\n";

echo "\n=== 4. PHÍA NGƯỜI NHẬN: xác minh -> giải mã -> giải nén ===\n";
$payload   = file_get_contents($path);
$signature = file_get_contents("$path.sig");
echo '  Chữ ký hợp lệ   : ', SigningExporter::verify($payload, $signature, $publicKey) ? 'CÓ' : 'KHÔNG', "\n";
$plain = CompressingExporter::decompress(EncryptingExporter::decrypt($payload, $aesKey));
echo '  Dữ liệu gốc     : ', number_format(strlen($plain)), " bytes, ", substr_count($plain, "\n"), " dòng CSV\n";
echo '  3 dòng đầu      : ', str_replace("\n", ' | ', implode("\n", array_slice(explode("\n", ltrim($plain, "\xEF\xBB\xBF")), 0, 3))), "\n";

$tampered = $payload;
$tampered[100] = chr(ord($tampered[100]) ^ 1);
echo '  File bị sửa 1bit: chữ ký hợp lệ? ', SigningExporter::verify($tampered, $signature, $publicKey) ? 'CÓ' : 'KHÔNG', "\n";
