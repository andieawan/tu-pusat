<?php

// Konfigurasi khusus aplikasi TU (Tata Usaha).
return [
    'school_name' => env('SCHOOL_NAME', 'SMK Islam Bustanul Ulum Pakusari'),
    'timezone' => env('TU_TIMEZONE', 'Asia/Jakarta'),
    // Singkatan sekolah pada nomor surat keluar: 001/KET/SMKIBU/X/2026
    'kode_surat' => env('KODE_SURAT', 'SMKIBU'),


    // Admin lokal darurat (tu:buat-admin). Login normal lewat Pusat.
    'login_lokal' => (bool) env('TU_LOGIN_LOKAL', true),

    // Identitas pada kop surat (sama dengan BK/Absensi; ubah lewat .env)
    'surat' => [
        'yayasan' => env('SURAT_YAYASAN', 'YAYASAN PENDIDIKAN ISLAM'),
        'yayasan2' => env('SURAT_YAYASAN2', '“ BUSTANUL ULUM ”'),
        'nama' => env('SURAT_NAMA', 'SMK ISLAM BUSTANUL ULUM PAKUSARI'),
        'nss' => env('SURAT_NSS', '342052423288'),
        'npsn' => env('SURAT_NPSN', '20570966'),
        'bidang' => env('SURAT_BIDANG', 'Kelompok Bisnis Manajemen dan Teknologi Informasi Komunikasi'),
        'alamat' => env('SURAT_ALAMAT', 'Jl. Himalaya No. 17 Telp. (0331) 7255753 Kode Pos. 68181 Pakusari – Jember'),
        'kota' => env('SURAT_KOTA', 'Pakusari'),
        'kepsek' => env('SURAT_KEPSEK', 'MUHAMMAD MUSLIM, S.Pd., Gr'),
        'kepsek_nuptk' => env('SURAT_KEPSEK_NUPTK', '7951767668130072'),
        'nomor_awal' => env('SURAT_NOMOR_AWAL', '400.3.8.1'),
        'nomor_akhir' => env('SURAT_NOMOR_AKHIR', '101.6.20570966'),
    ],
];
