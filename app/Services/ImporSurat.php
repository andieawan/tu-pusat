<?php

namespace App\Services;

use App\Exceptions\UserError;
use App\Models\TuSurat;
use App\Models\User;
use App\Support\Dates;
use App\Support\Xlsx;
use Illuminate\Support\Facades\DB;

/**
 * Impor register surat lama (buku agenda/Excel): lembar SURAT MASUK dan SURAT KELUAR, satu baris = satu surat.
 * No. Agenda lama boleh diisi (harus unik per arah per tahun); kosong = nomor berikutnya. Nomor surat tidak dibuat
 * otomatis untuk surat lama. Semua baris diperiksa dulu; bila ada satu kesalahan, tidak ada yang disimpan.
 * Surat yang sama (arah + tanggal + pihak + perihal) yang sudah ada dilewati, jadi aman diulang.
 */
class ImporSurat
{
    public const KOLOM = [
        'masuk' => ['urut' => 'No. Agenda', 'tanggal' => 'Tanggal Diterima', 'nomor' => 'Nomor Surat', 'pihak' => 'Pengirim', 'perihal' => 'Perihal',
            'isi' => 'Isi Ringkas', 'status' => 'Status', 'disposisi' => 'Disposisi'],
        'keluar' => ['urut' => 'No. Agenda', 'tanggal' => 'Tanggal Surat', 'nomor' => 'Nomor Surat', 'pihak' => 'Tujuan', 'perihal' => 'Perihal', 'isi' => 'Isi Ringkas'],
    ];

    public const LEMBAR = ['masuk' => 'SURAT MASUK', 'keluar' => 'SURAT KELUAR'];

    public static function templat(): array
    {
        $petunjuk = [['PETUNJUK IMPOR REGISTER SURAT LAMA'], [''],
            ['1. Isi lembar SURAT MASUK dan/atau SURAT KELUAR mulai baris ke-2. Jangan ubah judul kolom dan nama lembar.'],
            ['2. Wajib: Tanggal (YYYY-MM-DD atau DD/MM/YYYY), Pengirim/Tujuan, Perihal.'],
            ['3. No. Agenda: nomor urut di buku agenda lama (unik per tahun). Kosong = diberi nomor berikutnya otomatis.'],
            ['4. Nomor Surat diisi apa adanya; surat keluar lama tanpa nomor tetap kosong (tidak dibuatkan nomor baru).'],
            ['5. Status surat masuk: '.implode(', ', TuService::STATUS_MASUK).' (kosong = Selesai, karena surat lama).'],
            ['6. Scan surat tidak ikut diimpor; unggah lewat menu Surat Masuk/Keluar bila perlu.'],
            ['7. Bila ada satu baris salah, tidak ada yang disimpan dan kesalahannya ditampilkan. Pakai "Periksa saja" dulu.']];

        return ['PETUNJUK' => ['rows' => $petunjuk, 'tebal' => [0]],
            self::LEMBAR['masuk'] => [array_values(self::KOLOM['masuk'])], self::LEMBAR['keluar'] => [array_values(self::KOLOM['keluar'])]];
    }

    /** @return array{masuk:int, keluar:int, dilewati:int} */
    public static function proses(User $u, string $path, string $nama, bool $uji): array
    {
        $lembarBerkas = self::namaLembar($path, $nama);
        $isi = [];
        foreach (Xlsx::readAllSheets($path, $nama) as $i => $rows) {
            $isi[$lembarBerkas[$i] ?? ''] = $rows;
        }
        $galat = [];
        $antre = [];
        $dilewati = 0;
        $pakai = []; // "arah|tahun|urut" yang dipakai di berkas ini
        $ada = [];   // surat yang sudah masuk antrean (cegah dobel dalam satu berkas)
        foreach (self::LEMBAR as $arah => $lembar) {
            $rows = $isi[$lembar] ?? null;
            if (! $rows) {
                continue;
            }
            $hdr = array_map(fn ($v) => mb_strtolower(trim((string) $v)), array_shift($rows));
            $kol = array_map(fn ($j) => array_search(mb_strtolower($j), $hdr, true), self::KOLOM[$arah]);
            foreach (['tanggal', 'pihak', 'perihal'] as $w) {
                if ($kol[$w] === false) {
                    throw new UserError("Lembar $lembar: kolom \"".self::KOLOM[$arah][$w].'" tidak ditemukan. Pakai templat dari halaman ini.');
                }
            }
            $ambil = fn (array $r, string $k) => ($kol[$k] ?? false) === false ? '' : trim((string) ($r[$kol[$k]] ?? ''));
            foreach ($rows as $n => $r) {
                if (trim(implode('', array_map('strval', $r))) === '') {
                    continue;
                }
                $baris = "$lembar baris ".($n + 2);
                $err = [];
                $tgl = self::tanggal($ambil($r, 'tanggal'));
                $d = ['arah' => $arah, 'jenis' => 'biasa', 'tanggal' => $tgl, 'nomor' => mb_substr($ambil($r, 'nomor'), 0, 80) ?: null,
                    'pihak' => $ambil($r, 'pihak'), 'perihal' => $ambil($r, 'perihal'), 'isi' => $ambil($r, 'isi') ?: null];
                if (! $tgl) {
                    $err[] = 'tanggal "'.$ambil($r, 'tanggal').'" tidak valid';
                } elseif (Dates::isFuture($tgl)) {
                    $err[] = "tanggal $tgl di masa depan";
                }
                foreach (['pihak', 'perihal'] as $k) {
                    if ($d[$k] === '') {
                        $err[] = self::KOLOM[$arah][$k].' kosong';
                    } elseif (mb_strlen($d[$k]) > 191) {
                        $err[] = self::KOLOM[$arah][$k].' lebih dari 191 karakter';
                    }
                }
                if ($arah === 'masuk') {
                    $st = $ambil($r, 'status');
                    $d['status'] = $st === '' ? 'Selesai' : (collect(TuService::STATUS_MASUK)->first(fn ($o) => mb_strtolower($o) === mb_strtolower($st)));
                    if (! $d['status']) {
                        $err[] = "status \"$st\" harus ".implode('/', TuService::STATUS_MASUK);
                    }
                    $d['disposisi'] = mb_substr($ambil($r, 'disposisi'), 0, 191) ?: null;
                }
                $sama = $arah.'|'.$tgl.'|'.mb_strtolower($d['pihak'].'|'.$d['perihal']);
                if (! $err && (isset($ada[$sama]) || TuSurat::where(['arah' => $arah, 'tanggal' => $tgl, 'pihak' => $d['pihak'], 'perihal' => $d['perihal']])->exists())) {
                    $dilewati++; // sudah pernah diimpor/dicatat, atau dobel di berkas

                    continue;
                }
                $ada[$sama] = true;
                $urut = $ambil($r, 'urut');
                if ($urut !== '' && ! ctype_digit($urut)) {
                    $err[] = "No. Agenda \"$urut\" harus angka";
                } elseif ($urut !== '' && $tgl) {
                    $k = $arah.'|'.substr($tgl, 0, 4).'|'.(int) $urut;
                    if (isset($pakai[$k]) || TuSurat::where(['arah' => $arah, 'tahun' => (int) substr($tgl, 0, 4), 'urut' => (int) $urut])->exists()) {
                        $err[] = 'No. Agenda '.(int) $urut.' tahun '.substr($tgl, 0, 4).' sudah dipakai';
                    }
                    $pakai[$k] = true;
                }
                if ($err) {
                    $galat[] = "$baris: ".implode('; ', $err).'.';

                    continue;
                }
                $antre[] = [$d, $urut === '' ? null : (int) $urut];
            }
        }
        if ($galat) {
            throw new UserError(count($galat).' baris bermasalah — belum ada yang disimpan. Perbaiki lalu unggah lagi.'."\n".implode("\n", $galat));
        }
        if (! $antre && ! $dilewati) {
            throw new UserError('Tidak ada surat yang terisi. Isi lembar SURAT MASUK / SURAT KELUAR di templat.');
        }
        usort($antre, fn ($a, $b) => [$a[0]['tanggal'], $a[1] ?? PHP_INT_MAX] <=> [$b[0]['tanggal'], $b[1] ?? PHP_INT_MAX]);
        $hasil = ['masuk' => 0, 'keluar' => 0, 'dilewati' => $dilewati];
        DB::beginTransaction();
        try {
            // Nomor agenda tertulis dipasang dulu, baru yang kosong diberi nomor berikutnya (tidak bentrok).
            foreach ([true, false] as $bernomor) {
                foreach ($antre as [$d, $urut]) {
                    if (($urut !== null) !== $bernomor) {
                        continue;
                    }
                    $tahun = (int) substr($d['tanggal'], 0, 4);
                    $urut ??= (int) TuSurat::where('arah', $d['arah'])->where('tahun', $tahun)->max('urut') + 1;
                    TuSurat::create($d + ['urut' => $urut, 'tahun' => $tahun, 'dicatat_oleh' => $u->id]);
                    $hasil[$d['arah']]++;
                }
            }
            $uji ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        if (! $uji) {
            Audit::catat('Impor register surat lama', $u->nama, "{$hasil['masuk']} surat masuk, {$hasil['keluar']} surat keluar".($dilewati ? ", $dilewati sudah ada" : ''));
        }

        return $hasil;
    }

    /** YYYY-MM-DD, DD/MM/YYYY, atau nomor seri tanggal Excel → YYYY-MM-DD. */
    private static function tanggal(string $v): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) {
            $v = substr($v, 0, 10);
        } elseif (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})#', $v, $m)) {
            $v = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        } elseif (preg_match('/^\d{5}(\.\d+)?$/', $v) && (int) $v > 30000 && (int) $v < 80000) {
            $v = gmdate('Y-m-d', ((int) $v - 25569) * 86400);
        }

        return Dates::valid($v) ? $v : null;
    }

    /** Nama lembar .xlsx sesuai urutan readAllSheets. */
    private static function namaLembar(string $path, string $nama): array
    {
        if (strtolower(pathinfo($nama, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new UserError('Berkas harus .xlsx (templat dari halaman ini).');
        }
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return [];
        }
        $xml = (string) $zip->getFromName('xl/workbook.xml');
        $zip->close();
        preg_match_all('/<sheet\b[^>]*\bname="([^"]*)"/', $xml, $m);

        return array_map(fn ($n) => mb_strtoupper(trim(html_entity_decode($n, ENT_QUOTES | ENT_XML1, 'UTF-8'))), $m[1]);
    }
}
