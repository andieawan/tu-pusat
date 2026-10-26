<?php

namespace App\Http\Controllers;

use App\Models\AbsenPegawai;
use App\Models\MutasiSiswa;
use App\Models\Siswa;
use App\Models\TuSurat;
use App\Services\AbsenService;
use App\Support\Dates;

/** Beranda: ringkasan TU untuk petugas; guru/staf biasa langsung ke Absen Saya. */
class BerandaController extends Controller
{
    public function __invoke()
    {
        $u = $this->me();
        if (! $u->bisaLihat()) {
            return AbsenService::pegawaiSaya($u) ? redirect()->route('absen.saya') : view('beranda_kosong');
        }
        $today = Dates::today();
        $bulan = substr($today, 0, 7);

        return view('beranda', [
            'siswaAktif' => Siswa::aktif()->count(),
            'mutasiMasuk' => MutasiSiswa::where('jenis', 'masuk')->where('tanggal', 'like', "$bulan%")->count(),
            'mutasiKeluar' => MutasiSiswa::where('jenis', 'keluar')->where('tanggal', 'like', "$bulan%")->count(),
            'masukBelum' => TuSurat::where('arah', 'masuk')->where('status', '!=', 'Selesai')->count(),
            'keluarBulan' => TuSurat::where('arah', 'keluar')->where('tanggal', 'like', "$bulan%")->count(),
            'pegawai' => AbsenService::pegawai()->count(), 'absenHariIni' => AbsenPegawai::where('tanggal', $today)->count(),
            'terbaru' => TuSurat::with('siswa')->orderByDesc('id')->limit(6)->get(),
        ]);
    }
}
