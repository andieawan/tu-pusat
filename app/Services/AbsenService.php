<?php

namespace App\Services;

use App\Exceptions\UserError;
use App\Models\AbsenPegawai;
use App\Models\Pegawai;
use App\Models\Pengaturan;
use App\Models\User;
use App\Support\Dates;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * Absensi guru & staf. Yang diabsen: pegawai aktif dari Pusat. TU mengisi harian; pegawai yang punya akun
 * (peran "pegawai" di Pusat, tertaut ke data pegawainya) bisa absen mandiri dari HP, opsional dengan batas lokasi.
 */
class AbsenService
{
    public const STATUS = ['H' => 'Hadir', 'I' => 'Izin', 'S' => 'Sakit', 'A' => 'Alpa', 'D' => 'Dinas Luar'];

    /** @return Collection<int, Pegawai> */
    public static function pegawai(): Collection
    {
        return Pegawai::aktif()->orderBy('nama')->get();
    }

    /** @return array{created:int, updated:int} */
    public static function save(User $actor, string $tanggal, array $status, array $notes): array
    {
        if (! Dates::valid($tanggal) || Dates::isFuture($tanggal)) {
            throw new UserError('Tanggal tidak valid atau berada di masa depan.');
        }
        if (! $actor->isAdmin() && ! TuService::dalamBatasUbah($tanggal)) {
            throw new UserError('Absensi lebih dari 7 hari terkunci; hanya Administrator yang dapat mengubah.');
        }
        $created = $updated = 0;
        $ada = AbsenPegawai::where('tanggal', $tanggal)->get()->keyBy('pegawai_id');
        foreach (self::pegawai() as $p) {
            $st = isset(self::STATUS[$status[$p->id] ?? '']) ? $status[$p->id] : 'H';
            $note = mb_substr(trim((string) ($notes[$p->id] ?? '')), 0, 200) ?: null;
            if ($row = $ada[$p->id] ?? null) {
                $upd = ['status' => $st, 'catatan' => $note, 'dicatat_oleh' => $actor->id];
                if ($row->status !== $st) {
                    $upd['sumber'] = 'tu'; // dikoreksi TU; jam & penanda terlambat tetap tersimpan
                }
                $row->update($upd);
                $updated++;
            } else {
                AbsenPegawai::create(['pegawai_id' => $p->id, 'tanggal' => $tanggal, 'status' => $st, 'catatan' => $note, 'dicatat_oleh' => $actor->id]);
                $created++;
            }
        }
        Audit::catat('Absensi Guru & Staf', $actor->nama, "$tanggal: $created baru, $updated diperbarui");

        return ['created' => $created, 'updated' => $updated];
    }

    /** Rekap satu bulan ('YYYY-MM'). @return array<int, array{pegawai:Pegawai, H:int, I:int, S:int, A:int, D:int, T:int, total:int, rate:?float}> */
    public static function recap(string $bulan): array
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
            throw new UserError('Bulan tidak valid.');
        }
        $to = gmdate('Y-m-t', strtotime("$bulan-01 UTC"));
        $by = [];
        foreach (AbsenPegawai::whereBetween('tanggal', ["$bulan-01", $to])->selectRaw('pegawai_id, status, COUNT(*) AS n')->groupBy('pegawai_id', 'status')->get() as $r) {
            $by[$r->pegawai_id][$r->status] = (int) $r->n;
        }
        $late = AbsenPegawai::whereBetween('tanggal', ["$bulan-01", $to])->where('terlambat', true)
            ->selectRaw('pegawai_id, COUNT(*) AS n')->groupBy('pegawai_id')->pluck('n', 'pegawai_id')->all();
        $out = [];
        foreach (self::pegawai() as $p) {
            $c = $by[$p->id] ?? [];
            $total = array_sum($c);
            $out[] = ['pegawai' => $p, 'H' => $c['H'] ?? 0, 'I' => $c['I'] ?? 0, 'S' => $c['S'] ?? 0, 'A' => $c['A'] ?? 0, 'D' => $c['D'] ?? 0,
                'T' => (int) ($late[$p->id] ?? 0), 'total' => $total, 'rate' => $total ? round((($c['H'] ?? 0) + ($c['D'] ?? 0)) / $total * 100, 1) : null];
        }

        return $out;
    }

    // ---------------------------------------------------------------- Absen mandiri

    /** Pengaturan absen mandiri. */
    public static function config(): array
    {
        $lat = Pengaturan::ambil('absen_lat');
        $lng = Pengaturan::ambil('absen_lng');
        $ada = is_numeric($lat) && is_numeric($lng);

        return [
            'aktif' => (bool) (int) Pengaturan::ambil('absen_mandiri', '1'),
            'batas' => (string) Pengaturan::ambil('absen_jam_masuk', '07:15'),
            'lat' => $ada ? (float) $lat : null, 'lng' => $ada ? (float) $lng : null,
            'radius' => $ada ? max(0, (int) Pengaturan::ambil('absen_radius', 0)) : 0,
        ];
    }

    public static function nowHm(): string
    {
        return CarbonImmutable::now(Dates::tz())->format('H:i');
    }

    /** Jarak (meter) dua titik koordinat, rumus haversine. */
    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $r = 6371000;
        $a = deg2rad($lat2 - $lat1);
        $b = deg2rad($lng2 - $lng1);
        $h = sin($a / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($b / 2) ** 2;

        return (int) round(2 * $r * asin(min(1, sqrt($h))));
    }

    /** Pegawai aktif milik akun ini (absen mandiri), atau null. */
    public static function pegawaiSaya(User $u): ?Pegawai
    {
        $p = $u->aktif ? $u->pegawai() : null;

        return $p && $p->aktif ? $p : null;
    }

    public static function todayRow(Pegawai $p): ?AbsenPegawai
    {
        return AbsenPegawai::where('pegawai_id', $p->id)->where('tanggal', Dates::today())->first();
    }

    /** Status kartu "Absen Hari Ini", atau null bila fitur tidak berlaku untuk pengguna ini. */
    public static function state(?User $u): ?array
    {
        $p = $u ? self::pegawaiSaya($u) : null;
        if (! $p || ! self::config()['aktif']) {
            return null;
        }

        return ['pegawai' => $p, 'row' => self::todayRow($p), 'cfg' => self::config(), 'today' => Dates::today(), 'now' => self::nowHm()];
    }

    /** Memeriksa lokasi bila sekolah mengaktifkan batas radius. @return ?int jarak dalam meter */
    private static function checkLocation(array $cfg, mixed $lat, mixed $lng): ?int
    {
        if ($cfg['radius'] <= 0) {
            return is_numeric($lat) && is_numeric($lng) && $cfg['lat'] !== null ? self::distance((float) $lat, (float) $lng, $cfg['lat'], $cfg['lng']) : null;
        }
        if (! is_numeric($lat) || ! is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
            throw new UserError('Lokasi tidak terbaca. Izinkan akses lokasi (GPS) pada browser, lalu coba lagi. Absen mandiri memakai lokasi untuk memastikan Anda berada di sekolah.');
        }
        $d = self::distance((float) $lat, (float) $lng, $cfg['lat'], $cfg['lng']);
        if ($d > $cfg['radius']) {
            throw new UserError("Anda berada sekitar {$d} m dari sekolah (batas {$cfg['radius']} m). Absen mandiri hanya bisa dilakukan di sekolah; jika sedang bertugas di luar, gunakan Lapor Dinas Luar atau hubungi TU.");
        }

        return $d;
    }

    /** @return array{0:Pegawai,1:array} */
    private static function ensureOpen(User $u): array
    {
        $p = self::pegawaiSaya($u) ?? throw new UserError('Akun ini belum tertaut ke data guru/staf di Pusat. Hubungi admin.');
        $cfg = self::config();
        if (! $cfg['aktif']) {
            throw new UserError('Absen mandiri sedang dimatikan oleh sekolah. Absensi dicatat oleh TU.');
        }

        return [$p, $cfg];
    }

    public static function checkIn(User $u, mixed $lat = null, mixed $lng = null): AbsenPegawai
    {
        [$p, $cfg] = self::ensureOpen($u);
        $row = self::todayRow($p);
        if ($row && $row->jam_masuk) {
            throw new UserError("Anda sudah absen masuk pukul {$row->jam_masuk}.");
        }
        if ($row && $row->status !== 'H') {
            throw new UserError('Kehadiran hari ini sudah tercatat sebagai '.self::STATUS[$row->status].'. Jika keliru, hubungi TU.');
        }
        $jarak = self::checkLocation($cfg, $lat, $lng);
        $jam = self::nowHm();
        $vals = ['jam_masuk' => $jam, 'terlambat' => $jam > $cfg['batas'], 'jarak' => $jarak, 'sumber' => 'mandiri'];
        if ($row) {
            $row->update($vals);

            return $row;
        }
        try {
            return AbsenPegawai::create($vals + ['pegawai_id' => $p->id, 'tanggal' => Dates::today(), 'status' => 'H', 'dicatat_oleh' => $u->id]);
        } catch (QueryException) {
            throw new UserError('Absensi hari ini sudah tercatat. Muat ulang halaman.');
        }
    }

    public static function checkOut(User $u, mixed $lat = null, mixed $lng = null): AbsenPegawai
    {
        [$p, $cfg] = self::ensureOpen($u);
        $row = self::todayRow($p);
        if (! $row || $row->status !== 'H' || ! $row->jam_masuk) {
            throw new UserError('Absen masuk dulu sebelum absen pulang.');
        }
        if ($row->jam_pulang) {
            throw new UserError("Anda sudah absen pulang pukul {$row->jam_pulang}.");
        }
        self::checkLocation($cfg, $lat, $lng);
        $row->update(['jam_pulang' => self::nowHm()]);

        return $row;
    }

    /** Lapor tidak masuk hari ini: Izin, Sakit, atau Dinas Luar (catatan wajib). */
    public static function report(User $u, string $status, string $note): AbsenPegawai
    {
        [$p] = self::ensureOpen($u);
        if (! in_array($status, ['I', 'S', 'D'], true)) {
            throw new UserError('Pilih Izin, Sakit, atau Dinas Luar.');
        }
        $note = mb_substr(trim($note), 0, 200);
        if (mb_strlen($note) < 3) {
            throw new UserError('Isi keterangan singkat (mis. alasan izin atau tujuan dinas).');
        }
        $row = self::todayRow($p);
        if ($row && ! ($row->sumber === 'mandiri' && ! $row->jam_masuk)) {
            throw new UserError('Kehadiran hari ini sudah tercatat. Jika keliru, hubungi TU.');
        }
        $vals = ['status' => $status, 'catatan' => $note, 'sumber' => 'mandiri', 'terlambat' => false, 'dicatat_oleh' => $u->id];
        if ($row) {
            $row->update($vals);

            return $row;
        }

        return AbsenPegawai::create($vals + ['pegawai_id' => $p->id, 'tanggal' => Dates::today()]);
    }

    /** Riwayat absen milik sendiri. */
    public static function history(Pegawai $p, int $days = 30): Collection
    {
        return AbsenPegawai::where('pegawai_id', $p->id)->where('tanggal', '>=', gmdate('Y-m-d', strtotime(Dates::today().' UTC') - $days * 86400))->orderByDesc('tanggal')->get();
    }
}
