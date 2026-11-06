# TU — Tata Usaha SMKS Islam Bustanul Ulum

Aplikasi mandiri: register surat masuk/keluar, surat keterangan siswa bernomor otomatis, mutasi siswa (ditulis ke Pusat),
absen guru & staf (oleh TU dan absen mandiri dari HP dengan batas lokasi), serta cetak daftar hadir, daftar siswa, dan kartu siswa.
Login lewat Pusat Sekolah (SSO); kelas, siswa, guru & staf disalin dari Pusat.

Pemasangan: [docs/PASANG-TU.md](docs/PASANG-TU.md). Laravel 13, PHP 8.3+, MariaDB.

## Impor register surat lama
Menu **Impor** (Staf TU & Admin): unduh templat → isi lembar SURAT MASUK / SURAT KELUAR (satu baris = satu surat) →
*Periksa saja* → *Impor*. No. Agenda lama boleh diisi (unik per tahun; kosong = nomor berikutnya); nomor surat ditulis
apa adanya. Bila ada satu baris salah, tidak ada yang disimpan; surat yang sama yang sudah ada dilewati.
Kode: `app/Services/ImporSurat.php`.
