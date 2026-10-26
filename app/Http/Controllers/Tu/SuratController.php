<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TuSurat;
use App\Services\Audit;
use App\Services\TuService;
use App\Support\Dates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Register surat masuk & keluar, plus surat keterangan/dispensasi/izin bernomor otomatis. */
class SuratController extends Controller
{
    public function index(Request $request, string $arah)
    {
        $tahun = (int) ($request->query('tahun') ?: substr(Dates::today(), 0, 4));
        $status = in_array($request->query('status'), TuService::STATUS_MASUK, true) ? $request->query('status') : '';
        $q = trim((string) $request->query('q'));
        $t = str_replace(['%', '_'], ['\\%', '\\_'], $q);
        $list = TuSurat::with(['siswa', 'pencatat'])->where('arah', $arah)->where('tahun', $tahun)
            ->when($arah === 'masuk' && $status, fn ($x) => $x->where('status', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('perihal', 'like', "%$t%")->orWhere('pihak', 'like', "%$t%")->orWhere('nomor', 'like', "%$t%")))
            ->orderByDesc('urut')->paginate(25)->withQueryString();
        $ubah = $this->me()->bisaUbah();

        return view('tu.surat', [
            'arah' => $arah, 'list' => $list, 'tahun' => $tahun, 'status' => $status, 'q' => $q, 'canWrite' => $ubah, 'today' => Dates::today(),
            'years' => TuSurat::where('arah', $arah)->distinct()->orderByDesc('tahun')->pluck('tahun')->push($tahun)->unique()->sortDesc()->values(),
            'kelas' => Kelas::where('tahun_aktif', true)->orderBy('nama')->get(),
            'pickSiswa' => $ubah && $arah === 'keluar' ? Siswa::aktif()->orderBy('nama')->get()->groupBy('kelas_id') : collect(),
        ]);
    }

    public function store(Request $request, string $arah)
    {
        $request->validate(['berkas' => 'nullable|file|max:8192|mimes:jpg,jpeg,png,webp,heic,pdf']);
        $s = TuService::saveSurat($this->me(), $arah, null, $request->all(), $request->file('berkas'));

        return back()->with('success', 'Surat tercatat dengan No. agenda '.$s->urut.'/'.$s->tahun.($arah === 'keluar' ? ", nomor surat {$s->nomor}." : '.'));
    }

    public function update(Request $request, TuSurat $surat)
    {
        $request->validate(['berkas' => 'nullable|file|max:8192|mimes:jpg,jpeg,png,webp,heic,pdf']);
        TuService::saveSurat($this->me(), $surat->arah, $surat, $request->all(), $request->file('berkas'));

        return back()->with('success', 'Surat diperbarui.');
    }

    public function destroy(TuSurat $surat)
    {
        TuService::deleteSurat($this->me(), $surat);

        return back()->with('success', 'Surat dihapus dari register.');
    }

    public function keterangan(Request $request)
    {
        $s = TuService::keterangan($this->me(), $request->all());

        return redirect()->route('tu.surat', 'keluar')->with('success', "{$s->perihal} tercatat dengan nomor {$s->nomor}.")->with('print_url', route('tu.surat.cetak', $s));
    }

    public function berkas(TuSurat $surat)
    {
        abort_unless($surat->berkas_path, 404);

        return Storage::disk('local')->response($surat->berkas_path);
    }

    /** Cetak surat keluar (keterangan/dispensasi/izin/pindah, atau surat biasa berisi perihal & isi). */
    public function cetak(Request $request, TuSurat $surat)
    {
        abort_unless($surat->arah === 'keluar', 404);
        Audit::catat('Cetak Surat TU', $this->me()->nama, "{$surat->nomor} — {$surat->perihal}");

        return view('tu.cetak-surat', ['surat' => $surat->load('siswa.kelas'), 'tempat' => (string) $request->query('tempat'), 'nip' => (string) $request->query('nip'),
            'cfg' => config('tu.surat'), 'tahun' => (string) \App\Services\PusatKlien::baca('tahun_aktif', '')]);
    }
}
