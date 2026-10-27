<?php

namespace Tests\Feature;

use App\Services\Sambungan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Sambungan antar-aplikasi diatur dari Pusat (Administrasi → Sambungan): diambil lewat API, disimpan terenkripsi, menimpa .env. */
class SambunganPusatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        @unlink(Sambungan::berkas());
        config(['pusat.enabled' => true, 'pusat.url' => 'https://p.test', 'pusat.client_id' => 'c', 'pusat.client_secret' => 's', 'pusat.webhook_secret' => 'wh']);
    }

    protected function tearDown(): void
    {
        @unlink(Sambungan::berkas());
        parent::tearDown();
    }

    private function kirim(string $event, array $data, string $id)
    {
        $badan = json_encode(['event' => $event, 'data' => $data]);
        $t = time();

        return $this->call('POST', '/api/pusat/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_PUSAT_EVENT' => $event, 'HTTP_X_PUSAT_DELIVERY' => $id,
            'HTTP_X_PUSAT_TIMESTAMP' => $t, 'HTTP_X_PUSAT_SIGNATURE' => 'sha256='.hash_hmac('sha256', $t.'.'.$badan, 'wh'),
        ], $badan);
    }

    private function pusat(array $data, int $status = 200): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory); // ganti balasan palsu sebelumnya
        Http::fake(['p.test/api/v1/sambungan' => Http::response(['data' => $data], $status)]);
    }

    private function s(string $jenis, bool $aktif, string $url = '', string $kunci = ''): array
    {
        return ['jenis' => $jenis, 'aktif' => $aktif, 'peran' => 'x', 'mitra' => 'm', 'url' => $url, 'kunci' => $kunci];
    }

    public function test_webhook_mengisi_kunci_pkl_untuk_api_surat(): void
    {
        config(['tu.api_kunci' => []]);
        $k = str_repeat('t', 40);
        $this->getJson('/api/v1/ping', ['Authorization' => 'Bearer '.$k])->assertStatus(401);
        $this->pusat([$this->s('pkl-tu', true, 'https://pkl.test', $k)]);
        $this->kirim('sambungan.diperbarui', ['jenis' => 'pkl-tu'], 'd1')->assertOk();
        $this->getJson('/api/v1/ping', ['Authorization' => 'Bearer '.$k])->assertOk()->assertJsonPath('data.klien', 'pkl');

        $this->pusat([$this->s('pkl-tu', false)]);
        $this->kirim('sambungan.diperbarui', ['jenis' => 'pkl-tu'], 'd2')->assertOk();
        $this->getJson('/api/v1/ping', ['Authorization' => 'Bearer '.$k])->assertStatus(401);
    }
}
