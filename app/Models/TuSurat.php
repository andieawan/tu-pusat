<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuSurat extends Model
{
    protected $table = 'tu_surat';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['urut' => 'integer', 'tahun' => 'integer', 'siswa_id' => 'integer', 'extra' => 'array'];
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function x(string $key): string
    {
        return (string) ($this->extra[$key] ?? '');
    }
}
