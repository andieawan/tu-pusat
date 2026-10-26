<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Batas akses: "lihat" (TU/Kepsek/Admin), "ubah" (TU/Admin), "admin". */
class HanyaTu
{
    public function handle(Request $request, Closure $next, string $level = 'lihat')
    {
        $u = $request->user();
        $ok = match ($level) {
            'ubah' => $u && $u->bisaUbah(),
            'admin' => $u && $u->isAdmin(),
            default => $u && $u->bisaLihat(),
        };
        abort_unless($ok, 403, 'Anda tidak memiliki akses ke bagian ini.');

        return $next($request);
    }
}
