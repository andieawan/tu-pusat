<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiSiswa extends Model
{
    protected $table = 'mutasi_siswa';
    protected $guarded = [];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
}
