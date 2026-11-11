<?php

namespace Tests\Feature\Export;

use App\Services\Export\Decorator\CompressingExporter;
use App\Services\Export\Decorator\EncryptingExporter;
use App\Services\Export\Decorator\SigningExporter;
use App\Services\Export\Exporter\CsvExporter;
use App\Services\Export\Exporter\ExcelExporter;
use App\Services\Export\Exporter\JsonExporter;
use App\Services\Export\Exporter\PdfExporter;
use App\Services\Export\Model\ExportData;
use App\Services\Export\Service\ExporterBuilder;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportPatternsTest extends TestCase
{
    private function data(int $rows = 3): ExportData
    {
        return new ExportData(
            'Báo cáo',
            ['ID', 'Tên', 'Số tiền'],
            array_map(fn ($i) => [$i, "Khách hàng \"$i\"", $i * 1000], range(1, $rows)),
        );
    }

    #[Test]
    public function strategy_moi_dinh_dang_sinh_noi_dung_dung(): void
    {
        $csv = (new CsvExporter())->export($this->data());
        $this->assertSame('csv', $csv->extension);
        $this->assertStringContainsString('1,"Khách hàng ""1""",1000', $csv->content);

        $json = json_decode((new JsonExporter())->export($this->data())->content, true);
        $this->assertSame('Khách hàng "2"', $json['data'][1]['Tên']);

        $xls = (new ExcelExporter())->export($this->data());
        $this->assertNotFalse(simplexml_load_string($xls->content), 'Excel XML phải hợp lệ');

        $pdf = (new PdfExporter())->export($this->data());
        $this->assertStringStartsWith('%PDF-1.4', $pdf->content);
        $this->assertStringContainsString('Khach hang', $pdf->content);
    }

    #[Test]
    public function chain_chuyen_sang_du_phong_khi_that_bai(): void
    {
        Log::spy();
        $chain = new PdfExporter(new ExcelExporter(new CsvExporter(), maxRows: 5), maxRows: 5);

        $this->assertSame('csv', $chain->export($this->data(10))->extension);
        $this->assertSame('pdf', $chain->export($this->data(3))->extension);
        Log::shouldHaveReceived('warning')->twice();
    }

    #[Test]
    public function chain_nem_loi_khi_het_phuong_an(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tất cả phương án xuất đều thất bại');

        (new PdfExporter(maxRows: 1))->export($this->data(2));
    }

    #[Test]
    public function decorator_nen_ma_hoa_ky_so_va_dao_nguoc_duoc(): void
    {
        $aes = random_bytes(32);
        $rsa = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $pub = openssl_pkey_get_details($rsa)['key'];

        $result = ExporterBuilder::create()->format('csv')->compress()->encrypt($aes)->sign($rsa)->build()
            ->export($this->data());

        $this->assertSame('csv.gz.enc', $result->extension);
        $this->assertTrue(SigningExporter::verify($result->content, $result->metadata['signature'], $pub));

        $plain = CompressingExporter::decompress(EncryptingExporter::decrypt($result->content, $aes));
        $this->assertSame((new CsvExporter())->export($this->data())->content, $plain);

        $tampered = $result->content;
        $tampered[40] = chr(ord($tampered[40]) ^ 1);
        $this->assertFalse(SigningExporter::verify($tampered, $result->metadata['signature'], $pub));
        $this->expectException(\RuntimeException::class);
        EncryptingExporter::decrypt($tampered, $aes);
    }

    #[Test]
    public function export_data_tu_choi_dong_sai_so_cot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ExportData('x', ['a', 'b'], [[1]]);
    }
}
