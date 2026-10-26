<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\AbsenPegawai;
use App\Services\AbsenService;
use App\Services\TuService;
use App\Support\Dates;
use App\Support\Xlsx;
use Illuminate\Http\Request;

/** Absensi guru & staf oleh TU, rekap bulanan, unduh Excel. */
class AbsenController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = Dates::valid($request->query('date')) && ! Dates::isFuture($request->query('date')) ? $request->query('date') : Dates::today();
        $u = $this->me();

        return view('tu.absen', [
            'tanggal' => $tanggal, 'pegawai' => AbsenService::pegawai(),
            'existing' => AbsenPegawai::where('tanggal', $tanggal)->get()->keyBy('pegawai_id'),
            'canWrite' => $u->bisaUbah(), 'locked' => ! $u->isAdmin() && ! TuService::dalamBatasUbah($tanggal),
        ]);
    }

    public function store(Request $request)
    {
        $tanggal = (string) $request->input('date');
        $r = AbsenService::save($this->me(), $tanggal, (array) $request->input('status', []), (array) $request->input('notes', []));

        return redirect()->route('tu.absen', ['date' => $tanggal])->with('success', "Absensi guru & staf tersimpan ({$r['created']} baru, {$r['updated']} diperbarui).");
    }

    private function bulan(Request $request): string
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('bulan')) ? $request->query('bulan') : substr(Dates::today(), 0, 7);
    }

    public function rekap(Request $request)
    {
        $bulan = $this->bulan($request);
        [$y, $m] = array_map('intval', explode('-', $bulan));

        return view('tu.absen-rekap', ['bulan' => $bulan, 'label' => TuService::BULAN[$m].' '.$y, 'rows' => AbsenService::recap($bulan),
            'print' => $request->boolean('cetak'), 'cfg' => config('tu.surat')]);
    }

    public function unduh(Request $request)
    {
        $bulan = $this->bulan($request);
        $rows = [['No', 'Nama', 'NIP', 'Jabatan', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Dinas Luar', 'Terlambat', 'Hari Tercatat', '% Kehadiran']];
        foreach (AbsenService::recap($bulan) as $i => $r) {
            $rows[] = [$i + 1, $r['pegawai']->nama, (string) $r['pegawai']->nip, $r['pegawai']->label(), $r['H'], $r['I'], $r['S'], $r['A'], $r['D'], $r['T'], $r['total'], $r['rate'] === null ? '' : $r['rate'].'%'];
        }

        return Xlsx::download("Rekap_Absensi_Guru_Staf_$bulan.xlsx", ['Guru & Staf' => $rows]);
    }
}
