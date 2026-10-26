<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\Audit;
use App\Services\PusatKlien;
use App\Services\TuService;
use App\Support\Dates;
use Illuminate\Http\Request;

/** Cetak dokumen: daftar hadir kosong, daftar siswa, kartu siswa. */
class CetakController extends Controller
{
    private function kelas(Request $r): Kelas
    {
        return Kelas::find((int) $r->query('kelas')) ?? abort(404, 'Pilih kelas terlebih dahulu.');
    }

    private function siswa(Kelas $k)
    {
        return Siswa::aktif()->where('kelas_id', $k->id)->orderBy('nama')->get();
    }

    public function index()
    {
        return view('tu.cetak', ['kelas' => Kelas::where('tahun_aktif', true)->withCount(['siswa as aktif_count' => fn ($q) => $q->where('status', 'aktif')])->orderBy('nama')->get(),
            'bulan' => substr(Dates::today(), 0, 7)]);
    }

    public function hadir(Request $request)
    {
        $k = $this->kelas($request);
        $bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('bulan')) ? $request->query('bulan') : substr(Dates::today(), 0, 7);
        [$y, $m] = array_map('intval', explode('-', $bulan));
        $days = [];
        for ($d = 1, $n = (int) gmdate('t', strtotime("$bulan-01 UTC")); $d <= $n; $d++) {
            if (Dates::weekday(sprintf('%s-%02d', $bulan, $d)) !== 0) { // hari Minggu dilewati
                $days[] = $d;
            }
        }
        Audit::catat('Cetak Daftar Hadir', $this->me()->nama, "{$k->nama} $bulan");

        return view('tu.cetak-hadir', ['k' => $k, 'label' => TuService::BULAN[$m].' '.$y, 'days' => $days, 'siswa' => $this->siswa($k)]);
    }

    public function daftar(Request $request)
    {
        $k = $this->kelas($request);
        Audit::catat('Cetak Daftar Siswa', $this->me()->nama, $k->nama);

        return view('tu.cetak-daftar', ['kls' => $k, 'cols' => max(0, min(8, (int) $request->query('kolom', 3))), 'judul' => trim((string) $request->query('judul')),
            'siswa' => $this->siswa($k), 'tahun' => $k->tahun_ajaran ?: (string) PusatKlien::baca('tahun_aktif', '')]);
    }

    public function kartu(Request $request)
    {
        $k = $this->kelas($request);
        Audit::catat('Cetak Kartu Siswa', $this->me()->nama, $k->nama);

        return view('tu.cetak-kartu', ['k' => $k, 'siswa' => $this->siswa($k), 'tahun' => $k->tahun_ajaran ?: (string) PusatKlien::baca('tahun_aktif', '')]);
    }
}
