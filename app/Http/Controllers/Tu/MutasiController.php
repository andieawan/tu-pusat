<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\MutasiSiswa;
use App\Models\Siswa;
use App\Services\TuService;
use App\Support\Dates;
use Illuminate\Http\Request;

/** Mutasi siswa masuk & keluar (ditulis ke Pusat). */
class MutasiController extends Controller
{
    public function index(Request $request)
    {
        $jenis = in_array($request->query('jenis'), ['masuk', 'keluar'], true) ? $request->query('jenis') : '';
        $q = trim((string) $request->query('q'));
        $t = str_replace(['%', '_'], ['\\%', '\\_'], $q);
        $list = MutasiSiswa::with(['siswa', 'kelas'])
            ->when($jenis, fn ($x) => $x->where('jenis', $jenis))
            ->when($q !== '', fn ($x) => $x->whereIn('siswa_id', Siswa::where('nama', 'like', "%$t%")->orWhere('nis', 'like', "%$t%")->pluck('id')))
            ->orderByDesc('tanggal')->orderByDesc('id')->paginate(25)->withQueryString();
        $ubah = $this->me()->bisaUbah();

        return view('tu.mutasi', [
            'list' => $list, 'jenis' => $jenis, 'q' => $q, 'canWrite' => $ubah, 'today' => Dates::today(),
            'kelas' => Kelas::where('tahun_aktif', true)->orderBy('nama')->get(),
            'pickSiswa' => $ubah ? Siswa::aktif()->orderBy('nama')->get()->groupBy('kelas_id') : collect(),
        ]);
    }

    public function masuk(Request $request)
    {
        $s = TuService::mutasiMasuk($this->me(), $request->all());

        if (TuService::$tertunda) {
            return redirect()->route('tu.mutasi')->with('success', "Siswa masuk {$s->nama} tercatat di TU (kelas ".($s->kelas->nama ?? '-').').')
                ->with('warning', 'Pusat Sekolah sedang tidak bisa dihubungi; data siswa ini akan dikirim ke Pusat otomatis begitu Pusat hidup.');
        }

        return redirect()->route('tu.mutasi')->with('success', "Siswa masuk {$s->nama} tercatat di Pusat dan ditambahkan ke kelas ".($s->kelas->nama ?? '-').'.');
    }

    public function keluar(Request $request)
    {
        [$m, $surat] = TuService::mutasiKeluar($this->me(), $request->all(), $request->boolean('buat_surat'));
        $back = redirect()->route('tu.mutasi')->with('success', "Mutasi keluar {$m->siswa->nama} tercatat ".(TuService::$tertunda ? 'di TU' : 'di Pusat').' ('.TuService::alasanLabel($m->status_baru).').'
            .($surat ? " Surat keterangan pindah bernomor {$surat->nomor} dibuat." : ''));
        if (TuService::$tertunda) {
            $back->with('warning', 'Pusat Sekolah sedang tidak bisa dihubungi; perubahan status akan dikirim ke Pusat otomatis begitu Pusat hidup.');
        }

        return $surat ? $back->with('print_url', route('tu.surat.cetak', $surat)) : $back;
    }
}
