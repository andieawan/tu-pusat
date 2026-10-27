<?php

namespace Tests\Feature;

use App\Models\TuSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PKL meminta nomor Surat Keluar lewat API TU. */
class ApiSuratTest extends TestCase
{
    use RefreshDatabase;

    private const KUNCI = 'kunci-pkl-uji-1234567890abcdef';

    private function kirim(array $data, ?string $kunci = self::KUNCI)
    {
        return $this->postJson('/api/v1/surat-keluar', $data, $kunci ? ['Authorization' => 'Bearer '.$kunci] : []);
    }

    public function test_nomor_surat_pkl_tercatat_dan_idempoten(): void
    {
        config(['tu.api_kunci' => ['pkl' => self::KUNCI], 'tu.kode_surat' => 'SMKIBU']);
        $d = ['ref' => 'pengantar:periode-1:dudi-5', 'tanggal' => '2026-10-27', 'pihak' => 'Pimpinan CV Jaringan', 'perihal' => 'Permohonan Penempatan Siswa PKL — CV Jaringan', 'isi' => '3 siswa', 'petugas' => 'Kor'];
        $this->kirim($d, null)->assertStatus(401);
        $this->kirim($d, 'salah-salah-salah-salah-salah')->assertStatus(401);
        $r = $this->kirim($d)->assertCreated();
        $this->assertSame('001/PKL/SMKIBU/X/2026', $r->json('data.nomor'));
        $this->kirim($d)->assertOk()->assertJsonPath('data.nomor', '001/PKL/SMKIBU/X/2026'); // ulang: nomor sama
        $this->kirim(['ref' => 'penarikan:periode-1:dudi-5'] + $d)->assertCreated()->assertJsonPath('data.nomor', '002/PKL/SMKIBU/X/2026');
        $this->assertSame(2, TuSurat::where('jenis', 'pkl')->count());
        $this->kirim(['ref' => 'x y'] + $d)->assertStatus(422);

        $tu = User::create(['username' => 'tu', 'nama' => 'Tu', 'roles' => ['tu'], 'aktif' => true]);
        $this->actingAs($tu)->get('/surat/keluar')->assertOk()->assertSee('001/PKL/SMKIBU/X/2026')->assertSee('Surat PKL');
        $id = TuSurat::where('nomor', '001/PKL/SMKIBU/X/2026')->value('id');
        $this->get("/surat/$id/cetak")->assertOk()->assertSee('Permohonan Penempatan');
    }

    public function test_api_tertutup_tanpa_kunci_terpasang(): void
    {
        config(['tu.api_kunci' => []]);
        $this->kirim(['ref' => 'a'], '')->assertStatus(401);
    }
}
