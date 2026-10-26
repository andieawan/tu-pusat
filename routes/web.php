<?php

use App\Http\Controllers\AbsenSayaController;
use App\Http\Controllers\Admin\IntegrasiController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\PusatSsoController;
use App\Http\Controllers\PusatWebhookController;
use App\Http\Controllers\Tu\AbsenController;
use App\Http\Controllers\Tu\CetakController;
use App\Http\Controllers\Tu\MutasiController;
use App\Http\Controllers\Tu\SuratController;
use Illuminate\Support\Facades\Route;

// Penerima webhook dari Pusat Sekolah (tanda tangan HMAC; tanpa CSRF — lihat bootstrap/app.php).
Route::post('/api/pusat/webhook', PusatWebhookController::class)->middleware('throttle:120,1');

// Login: utama lewat Pusat (SSO), admin lokal darurat dengan password.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});
Route::get('/sso/login', [PusatSsoController::class, 'mulai'])->middleware('throttle:30,1')->name('sso.login');
Route::get('/sso/callback', [PusatSsoController::class, 'callback'])->middleware('throttle:30,1')->name('sso.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'akun.aktif'])->group(function () {
    Route::get('/', BerandaController::class)->name('beranda');

    // Absen mandiri guru & staf (akun yang tertaut ke data pegawai di Pusat)
    Route::get('/absen-saya', [AbsenSayaController::class, 'index'])->name('absen.saya');
    Route::post('/absen-saya', [AbsenSayaController::class, 'store'])->middleware('throttle:20,1')->name('absen.saya.simpan');

    // Lihat: Staf TU, Kepala Sekolah, Admin
    Route::middleware('tu:lihat')->name('tu.')->group(function () {
        Route::get('/surat/{arah}', [SuratController::class, 'index'])->whereIn('arah', ['masuk', 'keluar'])->name('surat');
        Route::get('/surat/{surat}/berkas', [SuratController::class, 'berkas'])->whereNumber('surat')->name('surat.berkas');
        Route::get('/surat/{surat}/cetak', [SuratController::class, 'cetak'])->whereNumber('surat')->name('surat.cetak');
        Route::get('/mutasi', [MutasiController::class, 'index'])->name('mutasi');
        Route::get('/absen-pegawai', [AbsenController::class, 'index'])->name('absen');
        Route::get('/absen-pegawai/rekap', [AbsenController::class, 'rekap'])->name('absen.rekap');
        Route::get('/absen-pegawai/rekap/unduh', [AbsenController::class, 'unduh'])->name('absen.unduh');
        Route::get('/cetak', [CetakController::class, 'index'])->name('cetak');
        Route::get('/cetak/daftar-hadir', [CetakController::class, 'hadir'])->name('cetak.hadir');
        Route::get('/cetak/daftar-siswa', [CetakController::class, 'daftar'])->name('cetak.daftar');
        Route::get('/cetak/kartu', [CetakController::class, 'kartu'])->name('cetak.kartu');
    });

    // Ubah: Staf TU & Admin
    Route::middleware('tu:ubah')->name('tu.')->group(function () {
        Route::post('/surat/{arah}', [SuratController::class, 'store'])->whereIn('arah', ['masuk', 'keluar'])->name('surat.simpan');
        Route::put('/surat/{surat}', [SuratController::class, 'update'])->whereNumber('surat')->name('surat.ubah');
        Route::delete('/surat/{surat}', [SuratController::class, 'destroy'])->whereNumber('surat')->name('surat.hapus');
        Route::post('/surat-keterangan', [SuratController::class, 'keterangan'])->name('keterangan');
        Route::post('/mutasi/masuk', [MutasiController::class, 'masuk'])->middleware('throttle:20,1')->name('mutasi.masuk');
        Route::post('/mutasi/keluar', [MutasiController::class, 'keluar'])->middleware('throttle:20,1')->name('mutasi.keluar');
        Route::post('/absen-pegawai', [AbsenController::class, 'store'])->name('absen.simpan');
    });

    Route::middleware('tu:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan');
        Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.simpan');
        Route::get('/integrasi', [IntegrasiController::class, 'index'])->name('integrasi');
        Route::post('/integrasi/uji-pusat', [IntegrasiController::class, 'ujiPusat'])->middleware('throttle:10,1')->name('integrasi.pusat');
        Route::post('/integrasi/sinkron', [IntegrasiController::class, 'sinkron'])->middleware('throttle:5,1')->name('integrasi.sinkron');
    });
});
