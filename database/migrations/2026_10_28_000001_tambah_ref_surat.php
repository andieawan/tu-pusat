<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Surat keluar dari aplikasi lain (PKL): kunci "ref" agar permintaan ulang tidak memakai nomor baru. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tu_surat', function (Blueprint $t) {
            $t->string('ref', 140)->nullable()->unique()->after('jenis');
        });
    }

    public function down(): void
    {
        Schema::table('tu_surat', function (Blueprint $t) {
            $t->dropUnique(['ref']);
            $t->dropColumn('ref');
        });
    }
};
