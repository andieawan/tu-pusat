<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** API TU untuk aplikasi lain: Authorization: Bearer <kunci> (TU_API_KUNCI_*). Nama aplikasi pemanggil disimpan di atribut "klien". */
class KunciApi
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) $request->bearerToken();
        foreach ((array) config('tu.api_kunci') as $nama => $kunci) {
            if ($token !== '' && is_string($kunci) && strlen($kunci) >= 16 && hash_equals($kunci, $token)) {
                $request->attributes->set('klien', $nama);

                return $next($request);
            }
        }

        return response()->json(['error' => ['kode' => 'kunci_salah', 'pesan' => 'Kunci API TU salah atau belum diatur.']], 401);
    }
}
