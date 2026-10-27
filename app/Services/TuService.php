<?php

namespace App\Services;

use App\Exceptions\UserError;
use App\Models\Kelas;
use App\Models\MutasiSiswa;
use App\Models\Siswa;
use App\Models\TuSurat;
use App\Models\User;
use App\Support\Dates;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Tata Usaha: register surat masuk/keluar, surat keterangan bernomor otomatis, mutasi siswa.
 * Data siswa milik Pusat: mutasi DITULIS ke Pusat lewat API (scope siswa_tulis), lalu salinan lokal
 * diperbarui dari jawaban Pusat. Bila Pusat menolak/tidak terjangkau, tidak ada yang berubah.
 */
class TuService
{
    public const KODE = ['biasa' => 'UM', 'aktif' => 'KET', 'pindah' => 'MUT', 'dispensasi' => 'DSP', 'izin' => 'IZN', 'pkl' => 'PKL'];
    /** Surat keluar yang dibuat aplikasi lain lewat API (dicetak di aplikasinya; di TU tercatat seperti surat biasa). */
    public const DARI_APLIKASI = ['pkl' => 'Surat PKL'];
    public const KETERANGAN = [
        'aktif' => 'Surat Keterangan Siswa Aktif', 'pindah' => 'Surat Keterangan Pindah Sekolah',
        'dispensasi' => 'Surat Dispensasi', 'izin' => 'Surat Izin',
    ];
    public const STATUS_MASUK = ['Baru', 'Diproses', 'Selesai'];
    /** Alasan keluar => [label, status siswa di Pusat]. */
    public const ALASAN_KELUAR = [
        'pindah' => ['Pindah ke sekolah lain', 'pindah'],
        'berhenti' => ['Mengundurkan diri', 'keluar'],
        'keluar' => ['Dikeluarkan', 'keluar'],
        'nonaktif' => ['Lainnya', 'keluar'],
    ];
    public const BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    private const ROMAWI = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    public static function alasanLabel(?string $k): string
    {
        return self::ALASAN_KELUAR[$k][0] ?? '';
    }

    public static function nomor(string $jenis, int $urut, string $tanggal): string
    {
        [$y, $m] = array_map('intval', explode('-', $tanggal));

        return sprintf('%03d/%s/%s/%s/%d', $urut, self::KODE[$jenis] ?? 'UM', config('tu.kode_surat', 'SMKIBU'), self::ROMAWI[$m], $y);
    }

    private static function str(array $in, string $k, int $max = 191): string
    {
        return mb_substr(trim((string) ($in[$k] ?? '')), 0, $max);
    }

    private static function date(array $in, string $k, string $label): string
    {
        $d = self::str($in, $k, 10);
        if (! Dates::valid($d)) {
            throw new UserError("$label tidak valid.");
        }

        return $d;
    }

    /** Surat & absensi lebih dari 7 hari terkunci kecuali untuk admin. */
    public static function dalamBatasUbah(string $tanggal): bool
    {
        return Dates::daysSince($tanggal) <= 7;
    }

    // ---- Register surat ------------------------------------------------------------------

    /** Simpan surat (baru/ubah). Surat keluar mendapat nomor otomatis bila kolom nomor dikosongkan. */
    public static function saveSurat(User $u, string $arah, ?TuSurat $s, array $in, ?UploadedFile $file = null): TuSurat
    {
        $tanggal = self::date($in, 'tanggal', $arah === 'masuk' ? 'Tanggal diterima' : 'Tanggal surat');
        $perihal = self::str($in, 'perihal');
        $pihak = self::str($in, 'pihak');
        if ($perihal === '' || $pihak === '') {
            throw new UserError(($arah === 'masuk' ? 'Pengirim' : 'Tujuan').' dan perihal wajib diisi.');
        }
        $data = ['tanggal' => $tanggal, 'perihal' => $perihal, 'pihak' => $pihak, 'isi' => trim((string) ($in['isi'] ?? '')) ?: null];
        if ($arah === 'masuk') {
            $st = (string) ($in['status'] ?? '');
            $data['status'] = in_array($st, self::STATUS_MASUK, true) ? $st : ($s->status ?? 'Baru');
            $data['disposisi'] = self::str($in, 'disposisi') ?: null;
        }
        if ($file) {
            $data['berkas_path'] = self::storeFile($file, $s?->berkas_path);
        }
        $nomorIn = self::str($in, 'nomor', 80);

        if ($s) {
            if ($nomorIn !== '') {
                $data['nomor'] = $nomorIn;
            }
            $s->update($data);
            Audit::catat('Ubah Surat '.ucfirst($arah), $u->nama, "{$s->nomor} — {$s->perihal}");

            return $s;
        }

        return self::create($u, ['arah' => $arah, 'jenis' => 'biasa'] + $data, $nomorIn);
    }

    /** Buat surat dengan nomor agenda berikutnya (ulang bila bentrok dengan input bersamaan). */
    private static function create(?User $u, array $data, string $nomorManual = ''): TuSurat
    {
        $tahun = (int) substr($data['tanggal'], 0, 4);
        for ($i = 0; ; $i++) {
            try {
                $urut = (int) TuSurat::where('arah', $data['arah'])->where('tahun', $tahun)->max('urut') + 1;
                $nomor = $nomorManual !== '' ? $nomorManual : ($data['arah'] === 'keluar' ? self::nomor($data['jenis'], $urut, $data['tanggal']) : null);
                $row = TuSurat::create($data + ['urut' => $urut, 'tahun' => $tahun, 'nomor' => $nomor, 'dicatat_oleh' => $u?->id]);
                break;
            } catch (QueryException $e) {
                if ($i >= 4) {
                    throw $e;
                }
            }
        }
        Audit::catat('Tambah Surat '.ucfirst($data['arah']), $u?->nama ?? 'Aplikasi '.strtoupper((string) ($data['jenis'] ?? '')), "No. agenda {$row->urut}/{$tahun} — {$row->perihal}");

        return $row;
    }

    /**
     * Surat keluar dari aplikasi lain (API). Idempoten lewat "ref": permintaan ulang dengan ref yang sama
     * mengembalikan surat yang sama (nomor tidak terpakai dua kali).
     */
    public static function suratDariAplikasi(string $aplikasi, array $in): TuSurat
    {
        if (! isset(self::DARI_APLIKASI[$aplikasi])) {
            throw new UserError('Aplikasi ini tidak boleh membuat surat.');
        }
        $ref = mb_substr(trim((string) ($in['ref'] ?? '')), 0, 120);
        if ($ref === '' || ! preg_match('/^[A-Za-z0-9:._\/-]+$/', $ref)) {
            throw new UserError('ref wajib diisi (huruf, angka, : . _ / -).');
        }
        $ref = $aplikasi.':'.$ref;
        if ($ada = TuSurat::where('ref', $ref)->first()) {
            return $ada;
        }
        $tanggal = self::date($in, 'tanggal', 'Tanggal surat');
        $perihal = self::str($in, 'perihal');
        $pihak = self::str($in, 'pihak');
        if ($perihal === '' || $pihak === '') {
            throw new UserError('Tujuan (pihak) dan perihal wajib diisi.');
        }

        return self::create(null, ['arah' => 'keluar', 'jenis' => $aplikasi, 'tanggal' => $tanggal, 'perihal' => $perihal,
            'pihak' => $pihak, 'isi' => mb_substr(trim((string) ($in['isi'] ?? '')), 0, 5000) ?: null, 'ref' => $ref,
            'extra' => ['petugas' => self::str($in, 'petugas', 100) ?: null]]);
    }

    /** Surat keterangan/dispensasi/izin untuk seorang siswa; otomatis tercatat di Register Surat Keluar. */
    public static function keterangan(User $u, array $in): TuSurat
    {
        $jenis = (string) ($in['jenis'] ?? '');
        if (! isset(self::KETERANGAN[$jenis])) {
            throw new UserError('Pilih jenis surat.');
        }
        $s = Siswa::with('kelas')->find((int) ($in['siswa_id'] ?? 0)) ?? throw new UserError('Pilih siswa terlebih dahulu.');
        $tanggal = self::date($in + ['tanggal' => Dates::today()], 'tanggal', 'Tanggal surat');
        $extra = ['keperluan' => self::str($in, 'keperluan'), 'alasan' => self::str($in, 'alasan'), 'sekolah_tujuan' => self::str($in, 'sekolah_tujuan')];
        if (in_array($jenis, ['dispensasi', 'izin'], true)) {
            $extra['mulai'] = self::date($in, 'mulai', 'Tanggal mulai');
            $extra['sampai'] = self::str($in, 'sampai', 10) === '' ? $extra['mulai'] : self::date($in, 'sampai', 'Tanggal selesai');
            if ($extra['sampai'] < $extra['mulai']) {
                throw new UserError('Tanggal selesai tidak boleh sebelum tanggal mulai.');
            }
            if ($extra['alasan'] === '') {
                throw new UserError('Alasan wajib diisi.');
            }
        }
        if ($jenis === 'pindah' && $extra['sekolah_tujuan'] === '') {
            throw new UserError('Sekolah tujuan wajib diisi.');
        }

        return self::create($u, [
            'arah' => 'keluar', 'jenis' => $jenis, 'tanggal' => $tanggal, 'siswa_id' => $s->id, 'extra' => $extra,
            'perihal' => self::KETERANGAN[$jenis].' — '.$s->nama,
            'pihak' => $jenis === 'pindah' ? $extra['sekolah_tujuan'] : ($extra['keperluan'] ?: 'Yang berkepentingan'),
        ]);
    }

    public static function deleteSurat(User $u, TuSurat $s): void
    {
        if (! $u->isAdmin() && ! self::dalamBatasUbah($s->tanggal)) {
            throw new UserError('Surat lebih dari 7 hari terkunci; hanya Administrator yang dapat menghapus.');
        }
        if ($s->berkas_path) {
            Storage::disk('local')->delete($s->berkas_path);
        }
        $s->delete();
        Audit::catat('Hapus Surat '.ucfirst($s->arah), $u->nama, "{$s->nomor} — {$s->perihal}");
    }

    private static function storeFile(UploadedFile $file, ?string $old): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic', 'pdf'], true)) {
            throw new UserError('Berkas scan harus berupa foto (JPG/PNG/WEBP/HEIC) atau PDF.');
        }
        if ($old) {
            Storage::disk('local')->delete($old);
        }

        return $file->storeAs('tu-surat', 'surat_'.bin2hex(random_bytes(8)).'.'.$ext, 'local');
    }

    // ---- Mutasi siswa (ditulis ke Pusat) ---------------------------------------------------

    /** Siswa pindahan/baru masuk: tambahkan di Pusat, salin ke TU, catat mutasi. */
    public static function mutasiMasuk(User $u, array $in): Siswa
    {
        $tanggal = self::date($in, 'tanggal', 'Tanggal masuk');
        if (Dates::isFuture($tanggal)) {
            throw new UserError('Tanggal masuk tidak boleh di masa depan.');
        }
        $sekolah = self::str($in, 'sekolah');
        if ($sekolah === '') {
            throw new UserError('Sekolah asal wajib diisi (isi "-" bila siswa baru).');
        }
        $nama = self::str($in, 'nama', 150);
        $nis = self::str($in, 'nis', 30);
        $jk = (string) ($in['jk'] ?? '');
        if ($nis === '' || $nama === '' || ! in_array($jk, ['L', 'P'], true)) {
            throw new UserError('NIS, nama, dan jenis kelamin wajib diisi.');
        }
        $kelas = Kelas::whereNull('dihapus_pada')->find((int) ($in['kelas_id'] ?? 0)) ?? throw new UserError('Pilih kelas tujuan.');

        $data = PusatKlien::tulis('post', '/api/v1/siswa', array_filter([
            'nis' => $nis, 'nama' => $nama, 'jenis_kelamin' => $jk, 'kelas_uuid' => $kelas->pusat_uuid,
            'nisn' => self::str($in, 'nisn', 20), 'tempat_lahir' => self::str($in, 'tempat_lahir', 100),
            'tanggal_lahir' => Dates::valid((string) ($in['tanggal_lahir'] ?? '')) ? $in['tanggal_lahir'] : null,
            'nama_ortu' => self::str($in, 'nama_ortu', 150), 'no_hp_ortu' => self::str($in, 'no_hp_ortu', 30), 'petugas' => $u->nama,
        ], fn ($v) => $v !== null && $v !== ''));

        return DB::transaction(function () use ($u, $in, $data, $tanggal, $sekolah, $kelas) {
            PusatSync::upsertSiswa($data, true);
            $s = Siswa::where('pusat_uuid', $data['uuid'])->firstOrFail();
            MutasiSiswa::create(['jenis' => 'masuk', 'siswa_id' => $s->id, 'kelas_id' => $kelas->id, 'tanggal' => $tanggal, 'sekolah' => $sekolah,
                'alasan' => self::str($in, 'alasan') ?: null, 'nomor_surat' => self::str($in, 'nomor_surat', 80) ?: null, 'dicatat_oleh' => $u->id]);
            Audit::catat('Mutasi Masuk', $u->nama, "{$s->nama} (NIS {$s->nis}) dari $sekolah");

            return $s;
        });
    }

    /** Siswa keluar: ubah status di Pusat, catat mutasi; surat pindah opsional. @return array{0:MutasiSiswa,1:?TuSurat} */
    public static function mutasiKeluar(User $u, array $in, bool $buatSurat = false): array
    {
        $s = Siswa::aktif()->find((int) ($in['siswa_id'] ?? 0)) ?? throw new UserError('Pilih siswa aktif terlebih dahulu.');
        $alasan = (string) ($in['status'] ?? '');
        if (! isset(self::ALASAN_KELUAR[$alasan])) {
            throw new UserError('Pilih jenis mutasi keluar.');
        }
        $tanggal = self::date($in, 'tanggal', 'Tanggal keluar');
        if (Dates::isFuture($tanggal)) {
            throw new UserError('Tanggal keluar tidak boleh di masa depan.');
        }
        $sekolah = self::str($in, 'sekolah');
        if ($alasan === 'pindah' && $sekolah === '') {
            throw new UserError('Sekolah tujuan wajib diisi untuk siswa yang pindah.');
        }

        $data = PusatKlien::tulis('patch', '/api/v1/siswa/'.$s->pusat_uuid, ['status' => self::ALASAN_KELUAR[$alasan][1], 'petugas' => $u->nama]);

        return DB::transaction(function () use ($u, $in, $s, $data, $alasan, $tanggal, $sekolah, $buatSurat) {
            $kelasId = $s->kelas_id;
            PusatSync::upsertSiswa($data, true);
            $m = MutasiSiswa::create(['jenis' => 'keluar', 'siswa_id' => $s->id, 'kelas_id' => $kelasId, 'tanggal' => $tanggal, 'sekolah' => $sekolah ?: null,
                'alasan' => self::str($in, 'alasan') ?: null, 'status_baru' => $alasan, 'dicatat_oleh' => $u->id]);
            Audit::catat('Mutasi Keluar', $u->nama, "{$s->nama} (NIS {$s->nis}) — ".self::alasanLabel($alasan).($sekolah ? " → $sekolah" : ''));
            $surat = null;
            if ($buatSurat && $alasan === 'pindah') {
                $surat = self::keterangan($u, ['jenis' => 'pindah', 'siswa_id' => $s->id, 'tanggal' => $tanggal, 'sekolah_tujuan' => $sekolah, 'alasan' => self::str($in, 'alasan')]);
                $m->update(['nomor_surat' => $surat->nomor]);
            }

            return [$m, $surat];
        });
    }
}
