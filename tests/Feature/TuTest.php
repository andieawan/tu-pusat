<?php

namespace Tests\Feature;

use App\Models\AbsenPegawai;
use App\Models\Kelas;
use App\Models\MutasiSiswa;
use App\Models\Pegawai;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\TuSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TuTest extends TestCase
{
    use RefreshDatabase;

    private Kelas $kelas;
    private Siswa $siswa;
    private Pegawai $guru;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pusat.enabled' => true, 'pusat.url' => 'https://pusat.test', 'pusat.client_id' => 'c', 'pusat.client_secret' => 's']);
        $this->kelas = Kelas::create(['pusat_uuid' => 'k1', 'nama' => 'X TKJ 1', 'tahun_ajaran' => '2026/2027', 'tahun_aktif' => true, 'wali_nama' => 'Bu Wali']);
        $this->siswa = Siswa::create(['pusat_uuid' => 's1', 'nis' => '1001', 'nama' => 'Budi', 'jk' => 'L', 'kelas_id' => $this->kelas->id, 'status' => 'aktif']);
        $this->guru = Pegawai::create(['pusat_uuid' => 'peg-1', 'nip' => 'g1', 'nama' => 'Guru Satu', 'jenis' => 'guru', 'aktif' => true]);
        Pegawai::create(['pusat_uuid' => 'peg-2', 'nama' => 'Staf Dua', 'jenis' => 'staf', 'aktif' => true]);
    }

    private function user(string $u, array $roles, ?string $pegawai = null): User
    {
        return User::create(['username' => $u, 'nama' => ucfirst($u), 'roles' => $roles, 'aktif' => true, 'pegawai_uuid' => $pegawai]);
    }

    public function test_hak_akses_per_peran(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->actingAs($this->user('kepsek', ['kepsek']))->get('/surat/keluar')->assertOk();
        $this->post('/surat/masuk', ['tanggal' => date('Y-m-d'), 'pihak' => 'x', 'perihal' => 'y'])->assertForbidden();
        $this->actingAs($this->user('guru', ['pegawai'], 'peg-1'))->get('/')->assertRedirect(route('absen.saya'));
        $this->get('/surat/keluar')->assertForbidden();
        $this->actingAs($this->user('tu', ['tu']))->get('/admin/pengaturan')->assertForbidden();
        $this->actingAs($this->user('lain', ['pegawai']))->get('/')->assertOk()->assertSee('Belum ada akses');
    }

    public function test_surat_bernomor_otomatis_dan_keterangan_siswa(): void
    {
        $this->actingAs($this->user('tu', ['tu']));
        $this->post('/surat/keluar', ['tanggal' => '2026-10-05', 'pihak' => 'Komite', 'perihal' => 'Pemberitahuan'])->assertRedirect();
        $this->post('/surat-keterangan', ['jenis' => 'aktif', 'siswa_id' => $this->siswa->id, 'tanggal' => '2026-10-06', 'keperluan' => 'beasiswa'])->assertRedirect();
        $this->assertSame(['001/UM/SMKIBU/X/2026', '002/KET/SMKIBU/X/2026'], TuSurat::orderBy('urut')->pluck('nomor')->all());
        $id = TuSurat::where('jenis', 'aktif')->value('id');
        $this->get("/surat/$id/cetak")->assertOk()->assertSee('Budi')->assertSee('beasiswa');
    }

    public function test_mutasi_masuk_ditulis_ke_pusat(): void
    {
        Http::fake(['pusat.test/api/v1/siswa' => Http::response(['data' => [
            'uuid' => 's-baru', 'nis' => '2001', 'nama' => 'Sari', 'jenis_kelamin' => 'P', 'status' => 'aktif', 'kelas_uuid' => 'k1', 'diperbarui' => now()->toIso8601String(),
        ]], 201)]);
        $this->actingAs($this->user('tu', ['tu']))
            ->post('/mutasi/masuk', ['nis' => '2001', 'nama' => 'Sari', 'jk' => 'P', 'kelas_id' => $this->kelas->id, 'tanggal' => date('Y-m-d'), 'sekolah' => 'SMK Lain'])
            ->assertRedirect(route('tu.mutasi'));

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['kelas_uuid'] === 'k1' && $r['nis'] === '2001' && $r['petugas'] === 'Tu');
        $baru = Siswa::where('pusat_uuid', 's-baru')->firstOrFail();
        $this->assertSame($this->kelas->id, $baru->kelas_id);
        $this->assertSame('SMK Lain', MutasiSiswa::firstOrFail()->sekolah);
    }

    public function test_mutasi_keluar_mengubah_status_di_pusat_dan_membuat_surat_pindah(): void
    {
        Http::fake(['pusat.test/api/v1/siswa/s1' => Http::response(['data' => ['uuid' => 's1', 'status' => 'pindah', 'kelas_uuid' => 'k1']])]);
        $this->actingAs($this->user('tu', ['tu']))
            ->post('/mutasi/keluar', ['siswa_id' => $this->siswa->id, 'status' => 'pindah', 'tanggal' => date('Y-m-d'), 'sekolah' => 'SMK Tujuan', 'buat_surat' => 1])
            ->assertRedirect(route('tu.mutasi'));

        Http::assertSent(fn (Request $r) => $r->method() === 'PATCH' && $r['status'] === 'pindah');
        $this->assertSame('pindah', $this->siswa->fresh()->status);
        $this->assertNotNull(MutasiSiswa::firstOrFail()->nomor_surat);
        $this->assertSame(1, TuSurat::where('jenis', 'pindah')->count());
    }

    public function test_pusat_menolak_tidak_ada_yang_berubah(): void
    {
        Http::fake(['pusat.test/*' => Http::response(['error' => ['kode' => 'nis_sudah_ada', 'pesan' => 'NIS 1001 sudah dipakai siswa lain.']], 409)]);
        $this->actingAs($this->user('tu', ['tu']))
            ->post('/mutasi/masuk', ['nis' => '1001', 'nama' => 'X', 'jk' => 'L', 'kelas_id' => $this->kelas->id, 'tanggal' => date('Y-m-d'), 'sekolah' => '-'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'NIS 1001 sudah dipakai'));
        $this->assertSame(0, MutasiSiswa::count());
        $this->assertSame(1, Siswa::count());
    }

    public function test_absen_pegawai_oleh_tu_dan_rekap(): void
    {
        $this->actingAs($this->user('tu', ['tu']));
        $p2 = Pegawai::where('pusat_uuid', 'peg-2')->first();
        $this->post('/absen-pegawai', ['date' => date('Y-m-d'), 'status' => [$this->guru->id => 'H', $p2->id => 'S'], 'notes' => [$p2->id => 'surat dokter']])->assertRedirect();
        $this->assertSame(2, AbsenPegawai::count());
        $this->get('/absen-pegawai/rekap')->assertOk()->assertSee('Staf Dua');
        $this->get('/absen-pegawai/rekap/unduh')->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_absen_mandiri_dan_batas_lokasi(): void
    {
        $guru = $this->user('guru', ['pegawai'], 'peg-1');
        $this->actingAs($guru)->post('/absen-saya', ['aksi' => 'masuk'])->assertSessionHas('success');
        $row = AbsenPegawai::where('pegawai_id', $this->guru->id)->firstOrFail();
        $this->assertSame('mandiri', $row->sumber);
        $this->post('/absen-saya', ['aksi' => 'pulang'])->assertSessionHas('success');

        AbsenPegawai::query()->delete();
        Pengaturan::simpan(['absen_lat' => -8.15, 'absen_lng' => 113.76, 'absen_radius' => 100]);
        $this->post('/absen-saya', ['aksi' => 'masuk', 'lat' => -8.20, 'lng' => 113.76])->assertSessionHas('error');
        $this->post('/absen-saya', ['aksi' => 'masuk', 'lat' => -8.1501, 'lng' => 113.7601])->assertSessionHas('success');
    }

    public function test_cetak_dokumen_kelas(): void
    {
        $this->actingAs($this->user('tu', ['tu']));
        $this->get('/cetak/daftar-hadir?kelas='.$this->kelas->id)->assertOk()->assertSee('Budi')->assertSee('Bu Wali');
        $this->get('/cetak/daftar-siswa?kelas='.$this->kelas->id)->assertOk()->assertSee('1001');
        $this->get('/cetak/kartu?kelas='.$this->kelas->id)->assertOk()->assertSee('KARTU SISWA');
    }

    public function test_pusat_mati_mutasi_diantrekan_lalu_terkirim(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('mati'));
        $tu = $this->user('tu', ['tu']);
        $this->actingAs($tu)
            ->post('/mutasi/masuk', ['nis' => '2001', 'nama' => 'Sari', 'jk' => 'P', 'kelas_id' => $this->kelas->id, 'tanggal' => date('Y-m-d'), 'sekolah' => 'SMK Lain'])
            ->assertRedirect(route('tu.mutasi'))->assertSessionHas('warning');
        $baru = Siswa::where('nis', '2001')->firstOrFail();
        $this->assertStringStartsWith('lokal-', $baru->pusat_uuid);
        $this->post('/mutasi/keluar', ['siswa_id' => $this->siswa->id, 'status' => 'pindah', 'tanggal' => date('Y-m-d'), 'sekolah' => 'SMK Tujuan'])
            ->assertSessionHas('warning');
        $this->assertSame('pindah', $this->siswa->fresh()->status);
        $this->assertSame(2, \App\Services\PusatAntrean::jumlah());

        // webhook lama dari Pusat tidak mengembalikan status yang belum terkirim
        \App\Services\PusatSync::upsertSiswa(['uuid' => 's1', 'status' => 'aktif', 'nama' => 'Budi']);
        $this->assertSame('pindah', $this->siswa->fresh()->status);

        // Pusat hidup lagi
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'pusat.test/api/v1/siswa/s1' => Http::response(['data' => ['uuid' => 's1', 'status' => 'pindah', 'kelas_uuid' => 'k1']]),
            'pusat.test/api/v1/siswa' => Http::response(['data' => ['uuid' => 'u-sari', 'nis' => '2001', 'nama' => 'Sari', 'jenis_kelamin' => 'P', 'status' => 'aktif', 'kelas_uuid' => 'k1']], 201),
        ]);
        $h = \App\Services\PusatAntrean::kirim();
        $this->assertSame(['terkirim' => 2, 'sisa' => 0], $h);
        $this->assertSame('u-sari', $baru->fresh()->pusat_uuid);
        $this->assertSame('pindah', $this->siswa->fresh()->status);
    }
}
