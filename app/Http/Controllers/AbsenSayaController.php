<?php

namespace App\Http\Controllers;

use App\Exceptions\UserError;
use App\Services\AbsenService;
use Illuminate\Http\Request;

/** Absen mandiri guru & staf: masuk, pulang, atau lapor izin/sakit/dinas luar (hanya untuk hari ini). */
class AbsenSayaController extends Controller
{
    public function index()
    {
        $p = AbsenService::pegawaiSaya($this->me());

        return view('absen-saya', ['state' => AbsenService::state($this->me()), 'tertaut' => (bool) $p, 'history' => $p ? AbsenService::history($p) : collect()]);
    }

    public function store(Request $request)
    {
        $me = $this->me();
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $msg = match ($request->input('aksi')) {
            'masuk' => ($r = AbsenService::checkIn($me, $lat, $lng)) && $r->terlambat
                ? "Absen masuk tercatat pukul {$r->jam_masuk} (terlambat)."
                : "Absen masuk tercatat pukul {$r->jam_masuk}. Selamat bertugas!",
            'pulang' => 'Absen pulang tercatat pukul '.AbsenService::checkOut($me, $lat, $lng)->jam_pulang.'. Terima kasih!',
            'lapor' => 'Laporan '.AbsenService::STATUS[AbsenService::report($me, (string) $request->input('status'), (string) $request->input('catatan'))->status].' hari ini tercatat.',
            default => throw new UserError('Aksi tidak dikenal.'),
        };

        return redirect()->back()->with('success', $msg);
    }
}
