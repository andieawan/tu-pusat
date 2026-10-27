<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\UserError;
use App\Http\Controllers\Controller;
use App\Services\TuService;
use Illuminate\Http\Request;

/** API TU untuk aplikasi lain: mengambil nomor & mencatat surat di Register Surat Keluar. */
class TuApiController extends Controller
{
    public function ping(Request $request)
    {
        return response()->json(['data' => ['ok' => true, 'aplikasi' => 'tu', 'klien' => $request->attributes->get('klien')]]);
    }

    public function suratKeluar(Request $request)
    {
        try {
            $s = TuService::suratDariAplikasi((string) $request->attributes->get('klien'), $request->all());
        } catch (UserError $e) {
            return response()->json(['error' => ['kode' => 'tidak_valid', 'pesan' => $e->getMessage()]], 422);
        }

        return response()->json(['data' => ['id' => $s->id, 'nomor' => $s->nomor, 'tanggal' => $s->tanggal, 'agenda' => $s->urut.'/'.$s->tahun,
            'baru' => $s->wasRecentlyCreated]], $s->wasRecentlyCreated ? 201 : 200);
    }
}
