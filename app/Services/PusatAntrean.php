<?php

namespace App\Services;

use App\Exceptions\UserError;
use App\Models\Siswa;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Antrean mutasi siswa ke Pusat. Bila Pusat tidak bisa dihubungi, mutasi tetap dicatat di TU dan dikirim otomatis nanti
 * (setelah request selesai paling sering tiap 2 menit, tiap 5 menit lewat cron, dan saat Sinkron).
 *  - siswa_baru   : kunci = UUID sementara "lokal-…" siswa di TU; setelah terkirim diganti UUID dari Pusat.
 *  - siswa_status : kunci = UUID siswa; status keluar/pindah.
 */
final class PusatAntrean
{
    public const LOKAL = 'lokal-';

    public static function siap(): bool
    {
        static $ada = null;

        return $ada ??= Schema::hasTable('pusat_antrean');
    }

    public static function uuidLokal(): string
    {
        return self::LOKAL.bin2hex(random_bytes(15)); // 36 karakter, sama panjang dengan UUID
    }

    public static function antre(string $jenis, string $kunci, array $data, ?string $galat = null): void
    {
        DB::table('pusat_antrean')->updateOrInsert(['jenis' => $jenis, 'kunci' => $kunci],
            ['data' => json_encode($data), 'percobaan' => 0, 'galat' => $galat, 'dibuat' => now('UTC')->format('Y-m-d H:i:s'), 'dicoba' => null]);
    }

    public static function ada(string $jenis, string $kunci): bool
    {
        return self::siap() && DB::table('pusat_antrean')->where(['jenis' => $jenis, 'kunci' => $kunci])->exists();
    }

    public static function jumlah(): int
    {
        return self::siap() ? DB::table('pusat_antrean')->count() : 0;
    }

    /** @return array<int,object> */
    public static function daftar(int $batas = 20): array
    {
        return self::siap() ? DB::table('pusat_antrean')->orderBy('id')->limit($batas)->get()->all() : [];
    }

    /** @return array{terkirim:int, sisa:int} */
    public static function kirim(): array
    {
        $h = ['terkirim' => 0, 'sisa' => 0];
        if (! self::siap() || ! PusatKlien::aktif()) {
            return $h;
        }
        foreach (DB::table('pusat_antrean')->orderBy('id')->get() as $b) {
            $b = DB::table('pusat_antrean')->where('id', $b->id)->first(); // kunci bisa berubah setelah siswa_baru terkirim
            if (! $b) {
                continue;
            }
            $data = (array) json_decode((string) $b->data, true);
            try {
                if ($b->jenis === 'siswa_baru') {
                    self::kirimSiswaBaru($b->kunci, $data);
                } elseif ($b->jenis === 'siswa_status') {
                    if (str_starts_with($b->kunci, self::LOKAL)) {
                        continue; // menunggu siswa barunya terkirim dulu
                    }
                    PusatSync::upsertSiswa(PusatKlien::tulis('patch', '/api/v1/siswa/'.$b->kunci, $data), true, true);
                }
                DB::table('pusat_antrean')->where('id', $b->id)->delete();
                $h['terkirim']++;
            } catch (UserError $e) {
                DB::table('pusat_antrean')->where('id', $b->id)->update([
                    'percobaan' => $b->percobaan + 1, 'dicoba' => now('UTC')->format('Y-m-d H:i:s'), 'galat' => mb_substr($e->getMessage(), 0, 500),
                ]);
                if ($e instanceof \App\Exceptions\PusatMati) {
                    break;
                }
            }
        }
        $h['sisa'] = self::jumlah();
        if ($h['terkirim']) {
            Audit::catat('Antrean Pusat', null, $h['terkirim'].' mutasi tertunda terkirim ke Pusat');
        }

        return $h;
    }

    private static function kirimSiswaBaru(string $lokal, array $data): void
    {
        $hasil = PusatKlien::tulis('post', '/api/v1/siswa', $data);
        DB::transaction(function () use ($lokal, $hasil) {
            Siswa::where('pusat_uuid', $lokal)->update(['pusat_uuid' => $hasil['uuid']]);
            DB::table('pusat_antrean')->where(['jenis' => 'siswa_status', 'kunci' => $lokal])->update(['kunci' => $hasil['uuid']]);
            PusatSync::upsertSiswa($hasil, true);
        });
    }

    public static function kirimBilaPerlu(): void
    {
        try {
            if (self::jumlah() > 0 && Cache::add('pusat_antrean_coba', 1, 120)) {
                self::kirim();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
