<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date:Y-m-d'];
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function scopeAktif($q)
    {
        return $q->where('status', 'aktif');
    }
}
