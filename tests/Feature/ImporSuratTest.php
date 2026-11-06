<?php

namespace Tests\Feature;

use App\Models\TuSurat;
use App\Models\User;
use App\Services\ImporSurat;
use App\Support\Xlsx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Impor register surat lama (semua-atau-tidak-sama-sekali). */
class ImporSuratTest extends TestCase
{
    use RefreshDatabase;

    private User $tu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tu = User::create(['username' => 'tu', 'nama' => 'Staf TU', 'roles' => ['tu'], 'aktif' => true]);
    }

    private function unggah(array $masuk = [], array $keluar = [], bool $uji = false)
    {
        $tpl = ImporSurat::templat();
        $tpl['SURAT MASUK'] = array_merge($tpl['SURAT MASUK'], $masuk);
        $tpl['SURAT KELUAR'] = array_merge($tpl['SURAT KELUAR'], $keluar);
        $path = tempnam(sys_get_temp_dir(), 'tu').'.xlsx';
        file_put_contents($path, Xlsx::build($tpl));

        return $this->actingAs($this->tu)->post('/impor', ['berkas' => new UploadedFile($path, 'r.xlsx', null, null, true), 'uji' => $uji ? 1 : 0]);
    }

    public function test_halaman_dan_templat(): void
    {
        $this->actingAs($this->tu)->get('/impor')->assertOk()->assertSee('SURAT MASUK');
        $res = $this->get('/impor/templat')->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'tp').'.xlsx';
        file_put_contents($path, $res->getContent());
        $s = Xlsx::readAllSheets($path, 'x.xlsx');
        $this->assertSame(array_values(ImporSurat::KOLOM['masuk']), $s[1][0]);
        $this->assertSame(array_values(ImporSurat::KOLOM['keluar']), $s[2][0]);
    }

    public function test_impor_dengan_dan_tanpa_no_agenda(): void
    {
        $masuk = [['12', '2025-07-14', '005/DISDIK/2025', 'Dinas Pendidikan', 'Undangan rapat', '', 'diproses', 'Kepsek'], ['', '15/07/2025', '', 'Komite', 'Iuran', '', '', '']];
        $keluar = [['', '2025-08-01', '001/UM/SMKIBU/VIII/2025', 'Puskesmas', 'Permohonan', 'Isi', ], ['', '2025-08-02', '', 'Kecamatan', 'Undangan', '']];
        $this->unggah($masuk, $keluar, true)->assertSessionHas('info');
        $this->assertSame(0, TuSurat::count());

        $this->unggah($masuk, $keluar)->assertSessionHas('success', fn ($m) => str_contains($m, '2 surat masuk, 2 surat keluar'));
        $a = TuSurat::where('perihal', 'Undangan rapat')->first();
        $this->assertSame([12, 2025, 'Diproses', 'Kepsek', '005/DISDIK/2025'], [$a->urut, $a->tahun, $a->status, $a->disposisi, $a->nomor]);
        $b = TuSurat::where('perihal', 'Iuran')->first();
        $this->assertSame([13, 'Selesai', '2025-07-15'], [$b->urut, $b->status, $b->tanggal]);
        $this->assertNull(TuSurat::where('perihal', 'Undangan')->value('nomor')); // surat lama tidak dibuatkan nomor
        $this->assertSame([1, 2], TuSurat::where('arah', 'keluar')->orderBy('urut')->pluck('urut')->all());

        $this->unggah($masuk, $keluar)->assertSessionHas('success', fn ($m) => str_contains($m, '4 surat sudah ada'));
        $this->assertSame(4, TuSurat::count());
    }

    public function test_satu_baris_salah_membatalkan_semua(): void
    {
        $this->unggah([['', '2025-07-14', '', 'Dinas', 'Rapat', '', '', '']], [
            ['x1', '2099-01-01', '', '', 'Tanpa tujuan', ''],
            ['3', '2025-08-01', '', 'A', 'Satu', ''], ['3', '2025-09-01', '', 'B', 'Dua', ''],
        ])->assertSessionHas('error')->assertSessionHas('galat', function ($g) {
            $t = implode("\n", $g);

            return str_contains($t, 'SURAT KELUAR baris 2') && str_contains($t, 'harus angka') && str_contains($t, 'masa depan')
                && str_contains($t, 'Tujuan kosong') && str_contains($t, 'No. Agenda 3 tahun 2025 sudah dipakai');
        });
        $this->assertSame(0, TuSurat::count());
    }

    public function test_kepsek_tidak_bisa_impor(): void
    {
        $this->actingAs(User::create(['username' => 'ks', 'nama' => 'Kepsek', 'roles' => ['kepsek'], 'aktif' => true]))->get('/impor')->assertForbidden();
    }
}
