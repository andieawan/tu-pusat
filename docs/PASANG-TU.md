# Memasang Aplikasi Tata Usaha (TU)

Aplikasi TU berdiri sendiri (1 aplikasi = 1 VM), login lewat **Pusat Sekolah**. Isinya: register surat masuk/keluar,
surat keterangan siswa bernomor otomatis, mutasi siswa (ditulis ke Pusat), absen guru & staf (oleh TU dan absen mandiri dari HP),
serta cetak daftar hadir, daftar siswa, dan kartu siswa.

## 1. VM baru
Repo private: ambil dengan token GitHub, lalu jalankan installer.
```bash
sudo git config --global credential.helper store
sudo git clone https://github.com/andieawan/tu-pusat.git /var/www/tu-pusat
cd /var/www/tu-pusat
sudo DOMAIN=tu.smkibupakusari.sch.id APP_URL=https://tu.smkibupakusari.sch.id PROXY_IP=192.168.10.10 bash deploy/install.sh
```
Lalu di NPMplus buat Proxy Host `tu.smkibupakusari.sch.id` → IP VM, port 80, SSL Let's Encrypt, Force SSL; dan DNS di Cloudflare.

## 2. Daftarkan di Pusat (VM Pusat)
Perbarui Pusat dulu (`sudo bash deploy/update.sh`) agar ada izin `siswa_tulis` dan peran `pegawai`, lalu:
```bash
cd /var/www/pusat-sekolah
sudo -u www-data php artisan pusat:aplikasi-baru tu --base-url=https://tu.smkibupakusari.sch.id
```
Templat "tu" otomatis memberi:
- Scope `tahun_ajaran`, `jurusan`, `kelas`, `siswa`, `pegawai`, `akun`, **`siswa_tulis`** (mutasi siswa ditulis ke Pusat)
- Peran `staf_tu` (petugas TU), `kepsek` (lihat saja), `admin`, `pegawai` (guru/staf: absen mandiri)

Salin `client_id`, `client_secret`, `webhook secret` (tampil sekali) ke `.env` TU:
```
PUSAT_ENABLED=true
PUSAT_URL=https://pusat.smkibupakusari.sch.id
PUSAT_CLIENT_ID=...
PUSAT_CLIENT_SECRET=...
PUSAT_WEBHOOK_SECRET=...
TRUSTED_PROXIES=192.168.10.10
```

## 3. Aktifkan
```bash
cd /var/www/tu-pusat
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan tu:periksa
sudo -u www-data php artisan tu:sinkron      # salin kelas, siswa, guru & staf dari Pusat
```

## 4. Akun & peran (di Pusat → Akun)
- Petugas TU: peran **staf_tu** untuk aplikasi Tata Usaha.
- Kepala Sekolah: **kepsek** (melihat semua, tidak mengubah).
- Guru & staf yang absen mandiri: **pegawai**, dan akunnya **ditautkan ke data pegawainya** (kolom Pegawai di form akun).
  Daftar yang diabsen TU = semua guru & staf aktif di Pusat (tidak perlu punya akun).

## 5. Pengaturan absen mandiri (Admin → Pengaturan)
Aktif/mati, batas jam masuk (lewat = terlambat), dan opsional titik koordinat + radius sekolah (absen hanya bisa di sekolah).

## 6. Mematikan modul TU lama di Absensi
Setelah TU berjalan, isi `TU_URL=https://tu.smkibupakusari.sch.id` di `.env` Absensi lalu `config:clear`.
Menu TU dan "Absen Saya" di Absensi berubah menjadi tautan ke aplikasi TU.

## Catatan mutasi
- **Siswa masuk**: data siswa ditambahkan di **Pusat**, lalu Absensi, BK, dan aplikasi lain menerimanya lewat webhook.
- **Siswa keluar**: status siswa di Pusat diubah (pindah/keluar); surat keterangan pindah bisa dibuat sekaligus.
- Bila Pusat tidak terjangkau atau menolak (mis. NIS sudah dipakai), tidak ada yang berubah dan pesannya ditampilkan.
- Nama, NIS, dan data pribadi siswa tetap diubah di panel Pusat.

## Pembaruan & cadangan
`sudo bash deploy/update.sh` (juga memasang jadwal backup harian) dan uji pulih `sudo bash deploy/uji-pulih.sh` — lihat docs/UJI-PULIH.md.
