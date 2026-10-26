@extends('layouts.app', ['title' => 'Cetak Dokumen'])
@section('content')
<div class="head"><div><h1>{{ 'Cetak Dokumen' }}</h1><small>{{ 'Pilih dokumen, lalu Cetak / Simpan sebagai PDF dari halaman pratinjau.' }}</small></div></div>
<div class="grid g3" style="align-items:start">
    <form method="get" action="{{ route('tu.cetak.hadir') }}" class="card" target="_blank"><h2>🗓 Daftar Hadir Kosong</h2><p class="mut" style="font-size:13px">Kolom tanggal satu bulan (hari Minggu dilewati) untuk diisi manual.</p>
        <div class="field"><label for="a-kelas">Kelas</label><select id="a-kelas" name="kelas" required>@foreach($kelas as $c)<option value="{{ $c->id }}">{{ $c->nama }} ({{ $c->aktif_count }})</option>@endforeach</select></div>
        <div class="field"><label for="a-bulan">Bulan</label><input id="a-bulan" type="month" name="bulan" value="{{ $bulan }}"></div>
        <button class="btn btn-pri">Buka Pratinjau</button></form>
    <form method="get" action="{{ route('tu.cetak.daftar') }}" class="card" target="_blank"><h2>📋 Daftar Siswa</h2><p class="mut" style="font-size:13px">Daftar nama per kelas dengan kolom kosong (mis. tanda tangan, nilai, iuran).</p>
        <div class="field"><label for="b-kelas">Kelas</label><select id="b-kelas" name="kelas" required>@foreach($kelas as $c)<option value="{{ $c->id }}">{{ $c->nama }} ({{ $c->aktif_count }})</option>@endforeach</select></div>
        <div class="fields"><div><label for="b-kol">Kolom kosong</label><input id="b-kol" type="number" name="kolom" min="0" max="8" value="3"></div><div><label for="b-jd">Judul <small>(opsional)</small></label><input id="b-jd" type="text" name="judul" placeholder="DAFTAR SISWA"></div></div>
        <button class="btn btn-pri" style="margin-top:12px">Buka Pratinjau</button></form>
    <form method="get" action="{{ route('tu.cetak.kartu') }}" class="card" target="_blank"><h2>🪪 Kartu Siswa</h2><p class="mut" style="font-size:13px">Kartu identitas sederhana (10 kartu per lembar A4) dengan kotak pas foto.</p>
        <div class="field"><label for="c-kelas">Kelas</label><select id="c-kelas" name="kelas" required>@foreach($kelas as $c)<option value="{{ $c->id }}">{{ $c->nama }} ({{ $c->aktif_count }})</option>@endforeach</select></div>
        <button class="btn btn-pri">Buka Pratinjau</button></form>
</div>
<p class="hint">Surat keterangan &amp; surat keluar dicetak dari <a href="{{ route('tu.surat', 'keluar') }}">Surat Keluar</a>. Rekap absensi guru dicetak dari <a href="{{ route('tu.absen.rekap') }}">Absen Pegawai → Rekap</a>.</p>
@endsection
