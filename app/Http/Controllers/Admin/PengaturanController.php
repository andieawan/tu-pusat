<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Services\AbsenService;
use App\Services\Audit;
use Illuminate\Http\Request;

/** Pengaturan absen mandiri guru & staf: aktif/mati, batas jam masuk, titik & radius sekolah. */
class PengaturanController extends Controller
{
    public function index()
    {
        return view('admin.pengaturan', ['cfg' => AbsenService::config()]);
    }

    public function update(Request $request)
    {
        $d = $request->validate([
            'jam_masuk' => 'required|date_format:H:i',
            'lat' => 'nullable|numeric|between:-90,90', 'lng' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:0|max:5000',
        ], [], ['jam_masuk' => 'batas jam masuk', 'lat' => 'lintang', 'lng' => 'bujur']);
        $adaTitik = $request->filled('lat') && $request->filled('lng');
        Pengaturan::simpan([
            'absen_mandiri' => $request->boolean('aktif') ? 1 : 0, 'absen_jam_masuk' => $d['jam_masuk'],
            'absen_lat' => $adaTitik ? $d['lat'] : null, 'absen_lng' => $adaTitik ? $d['lng'] : null,
            'absen_radius' => $adaTitik ? (int) ($d['radius'] ?? 0) : 0,
        ]);
        Audit::catat('Ubah pengaturan absen mandiri', $this->me()->nama, json_encode(AbsenService::config()));

        return back()->with('success', 'Pengaturan absen mandiri disimpan.');
    }
}
