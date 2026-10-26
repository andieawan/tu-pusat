<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Skema aplikasi TU: akun (dari Pusat), salinan data induk, register surat, mutasi, absen pegawai. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('username', 60)->unique();
            $t->string('nama');
            $t->string('email')->nullable();
            $t->string('password')->nullable();           // hanya admin lokal darurat
            $t->json('roles')->nullable();                // tu | kepsek | admin | pegawai
            $t->boolean('aktif')->default(true);
            $t->string('pusat_uuid', 36)->nullable()->unique();
            $t->string('pegawai_uuid', 36)->nullable()->index();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });

        // ---- Salinan data induk dari Pusat (hanya dibaca; mutasi ditulis ke Pusat lewat API) ----
        Schema::create('kelas', function (Blueprint $t) {
            $t->id();
            $t->string('pusat_uuid', 36)->unique();
            $t->string('nama');
            $t->string('tingkat', 10)->nullable();
            $t->string('jurusan', 30)->nullable();
            $t->string('tahun_ajaran', 20)->nullable();
            $t->boolean('tahun_aktif')->default(false);
            $t->string('wali_uuid', 36)->nullable()->index();
            $t->string('wali_nama')->nullable();
            $t->dateTime('pusat_diperbarui')->nullable();
            $t->dateTime('dihapus_pada')->nullable();
            $t->timestamps();
        });
        Schema::create('siswa', function (Blueprint $t) {
            $t->id();
            $t->string('pusat_uuid', 36)->unique();
            $t->string('nis', 30)->index();
            $t->string('nisn', 20)->nullable();
            $t->string('nama');
            $t->string('jk', 1)->nullable();
            $t->foreignId('kelas_id')->nullable()->index();
            $t->string('status', 16)->default('aktif')->index();
            $t->string('tempat_lahir', 100)->nullable();
            $t->date('tanggal_lahir')->nullable();
            $t->string('no_hp_ortu', 30)->nullable();
            $t->string('nama_ortu')->nullable();
            $t->dateTime('pusat_diperbarui')->nullable();
            $t->timestamps();
        });
        Schema::create('pegawai', function (Blueprint $t) {
            $t->id();
            $t->string('pusat_uuid', 36)->unique();
            $t->string('nip', 30)->nullable();
            $t->string('nama');
            $t->string('jenis', 20)->nullable();          // guru | staf
            $t->string('jabatan')->nullable();
            $t->boolean('aktif')->default(true)->index();
            $t->dateTime('pusat_diperbarui')->nullable();
            $t->timestamps();
        });

        // ---- Tata Usaha ----
        Schema::create('tu_surat', function (Blueprint $t) {
            $t->id();
            $t->string('arah', 10);                       // masuk | keluar
            $t->string('jenis', 16)->default('biasa');    // biasa | aktif | pindah | dispensasi | izin
            $t->integer('urut');                          // nomor agenda per arah per tahun
            $t->integer('tahun');
            $t->string('nomor', 80)->nullable();
            $t->string('tanggal', 10);
            $t->string('pihak', 191)->nullable();         // pengirim (masuk) / tujuan (keluar)
            $t->string('perihal', 191);
            $t->text('isi')->nullable();
            $t->string('status', 16)->nullable();         // surat masuk: Baru | Diproses | Selesai
            $t->string('disposisi', 191)->nullable();
            $t->foreignId('siswa_id')->nullable()->index();
            $t->json('extra')->nullable();                // keperluan, mulai, sampai, sekolah_tujuan, alasan
            $t->string('berkas_path')->nullable();
            $t->foreignId('dicatat_oleh')->nullable();
            $t->timestamps();
            $t->unique(['arah', 'tahun', 'urut']);
            $t->index(['arah', 'tanggal']);
        });
        Schema::create('mutasi_siswa', function (Blueprint $t) {
            $t->id();
            $t->string('jenis', 10);                      // masuk | keluar
            $t->foreignId('siswa_id')->index();
            $t->foreignId('kelas_id')->nullable();        // kelas saat mutasi
            $t->string('tanggal', 10);
            $t->string('sekolah', 191)->nullable();       // asal (masuk) / tujuan (keluar)
            $t->string('alasan', 191)->nullable();
            $t->string('status_baru', 16)->nullable();    // alasan keluar: pindah | berhenti | keluar | nonaktif
            $t->string('nomor_surat', 80)->nullable();
            $t->foreignId('dicatat_oleh')->nullable();
            $t->timestamps();
            $t->index(['jenis', 'tanggal']);
        });
        Schema::create('absen_pegawai', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pegawai_id');
            $t->string('tanggal', 10);
            $t->string('status', 1);                      // H I S A D
            $t->string('catatan', 200)->nullable();
            $t->string('jam_masuk', 5)->nullable();
            $t->string('jam_pulang', 5)->nullable();
            $t->boolean('terlambat')->default(false);
            $t->string('sumber', 10)->default('tu');      // tu | mandiri
            $t->integer('jarak')->nullable();             // meter dari sekolah saat absen masuk
            $t->foreignId('dicatat_oleh')->nullable();
            $t->timestamps();
            $t->unique(['pegawai_id', 'tanggal']);
            $t->index('tanggal');
        });
        Schema::create('pengaturan', function (Blueprint $t) {
            $t->string('kunci', 40)->primary();
            $t->text('nilai')->nullable();
            $t->timestamp('updated_at')->nullable();
        });

        // ---- Integrasi ----
        Schema::create('pusat_state', function (Blueprint $t) {
            $t->string('kunci', 40)->primary();
            $t->text('nilai')->nullable();
            $t->timestamp('updated_at')->nullable();
        });
        Schema::create('pusat_webhook', function (Blueprint $t) {
            $t->string('delivery_id', 64)->primary();
            $t->string('event', 60);
            $t->timestamp('created_at')->nullable()->index();
        });
        Schema::create('audit_log', function (Blueprint $t) {
            $t->id();
            $t->timestamp('waktu')->index();
            $t->string('aksi', 100);
            $t->string('pelaku')->nullable();
            $t->text('rincian')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['audit_log', 'pusat_webhook', 'pusat_state', 'pengaturan', 'absen_pegawai', 'mutasi_siswa', 'tu_surat', 'pegawai', 'siswa', 'kelas', 'sessions', 'password_reset_tokens', 'users'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
