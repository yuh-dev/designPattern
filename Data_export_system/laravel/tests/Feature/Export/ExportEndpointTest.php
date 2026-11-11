<?php

namespace Tests\Feature\Export;

use App\Models\User;
use App\Services\Export\Decorator\SigningExporter;
use App\Services\Export\Service\KeyStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['export.keys.encryption' => 'base64:' . base64_encode(random_bytes(32))]);
    }

    #[Test]
    public function tai_csv_qua_http(): void
    {
        User::factory()->create(['name' => 'Nguyễn Văn An']);

        $this->get('/exports/users.csv')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="users.csv"')
            ->assertSee('Nguyễn Văn An');
    }

    #[Test]
    public function pdf_qua_gioi_han_thi_fallback_sang_excel(): void
    {
        config(['export.max_rows.pdf' => 2]);
        User::factory(3)->create();

        $this->get('/exports/users.pdf')
            ->assertOk()
            ->assertHeader('X-Export-Format', 'xls');
    }

    #[Test]
    public function tai_file_da_ky_so_va_xac_minh_duoc(): void
    {
        $keys = app(KeyStore::class);
        $keys->generate();
        User::factory(2)->create();

        $res = $this->get('/exports/users.json?compress=1&encrypt=1&sign=1')->assertOk();

        $this->assertStringContainsString('users.json.gz.enc', $res->headers->get('Content-Disposition'));
        $this->assertTrue(SigningExporter::verify($res->getContent(), $res->headers->get('X-Export-Signature'), $keys->publicKey()));
    }

    #[Test]
    public function dinh_dang_khong_ho_tro_tra_404(): void
    {
        $this->get('/exports/users.docx')->assertNotFound();
    }

    #[Test]
    public function lenh_artisan_luu_file_vao_storage(): void
    {
        User::factory(2)->create();

        $this->artisan('export:users', ['format' => 'json', '--compress' => true])->assertSuccessful();

        $files = Storage::disk('local')->files('exports');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.json.gz', $files[0]);
    }
}
