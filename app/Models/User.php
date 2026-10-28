<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $guarded = [];

    protected $hidden = ['password', 'password_pusat', 'remember_token'];

    protected function casts(): array
    {
        return ['roles' => 'array', 'aktif' => 'boolean', 'password' => 'hashed'];
    }

    public function hasRole(string ...$r): bool
    {
        return (bool) array_intersect($r, $this->roles ?? []);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /** Petugas TU penuh (boleh mengubah): staf TU atau Admin. Superadmin Pusat otomatis berperan admin. */
    public function bisaUbah(): bool
    {
        return $this->hasRole('tu', 'admin');
    }

    /** Boleh membuka modul TU (Kepala Sekolah: baca saja). */
    public function bisaLihat(): bool
    {
        return $this->hasRole('tu', 'admin', 'kepsek');
    }

    /** Data pegawai (dari Pusat) milik akun ini, untuk absen mandiri. */
    public function pegawai(): ?Pegawai
    {
        return $this->pegawai_uuid ? Pegawai::where('pusat_uuid', $this->pegawai_uuid)->first() : null;
    }

    public function roleLabel(): string
    {
        $m = ['admin' => 'Admin', 'tu' => 'Staf TU', 'kepsek' => 'Kepala Sekolah', 'pegawai' => 'Guru/Staf'];

        return implode(', ', array_map(fn ($r) => $m[$r] ?? $r, $this->roles ?? []));
    }
}
