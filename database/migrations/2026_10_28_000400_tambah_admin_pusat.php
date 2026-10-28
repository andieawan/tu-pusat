<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Akun boleh membuka panel Administrasi Pusat (Operator TU / superadmin) → menu "Administrasi" di TU. Aman dijalankan ulang. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'admin_pusat')) {
            Schema::table('users', fn (Blueprint $t) => $t->boolean('admin_pusat')->default(false));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'admin_pusat')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('admin_pusat'));
        }
    }
};
