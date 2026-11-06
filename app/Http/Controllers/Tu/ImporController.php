<?php

namespace App\Http\Controllers\Tu;

use App\Exceptions\UserError;
use App\Http\Controllers\Controller;
use App\Services\ImporSurat;
use App\Support\Xlsx;
use Illuminate\Http\Request;

/** Impor register surat lama dari Excel: unduh templat, isi, "Periksa saja", lalu Impor. Staf TU & Admin. */
class ImporController extends Controller
{
    public function index()
    {
        return view('tu.impor');
    }

    public function templat()
    {
        return Xlsx::download('TEMPLAT_REGISTER_SURAT_LAMA.xlsx', ImporSurat::templat());
    }

    public function unggah(Request $request)
    {
        $request->validate(['berkas' => 'required|file|max:5120|mimes:xlsx', 'uji' => 'nullable|boolean'],
            ['berkas.required' => 'Pilih berkas Excel hasil isian templat.', 'berkas.mimes' => 'Berkas harus .xlsx (templat dari halaman ini).']);
        $f = $request->file('berkas');
        $uji = $request->boolean('uji');
        try {
            $h = ImporSurat::proses($this->me(), $f->getRealPath(), $f->getClientOriginalName(), $uji);
        } catch (UserError $e) {
            $baris = explode("\n", $e->getMessage());

            return back()->with('error', array_shift($baris))->with('galat', $baris);
        }
        $pesan = ($uji ? 'Periksa saja (belum disimpan): ' : 'Impor selesai: ')."{$h['masuk']} surat masuk, {$h['keluar']} surat keluar"
            .($h['dilewati'] ? "; {$h['dilewati']} surat sudah ada dan dilewati" : '').'.';

        return back()->with($uji ? 'info' : 'success', $pesan);
    }
}
