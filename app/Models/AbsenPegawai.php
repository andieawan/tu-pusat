<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenPegawai extends Model
{
    protected $table = 'absen_pegawai';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['pegawai_id' => 'integer', 'terlambat' => 'boolean', 'jarak' => 'integer'];
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }
}
