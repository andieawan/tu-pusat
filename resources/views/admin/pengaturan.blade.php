@extends('layouts.app', ['title' => 'Pengaturan'])
@section('content')
<div class="head"><div><h1>Pengaturan</h1><small>Absen mandiri guru &amp; staf dari HP.</small></div>
    <a class="btn btn-sm" href="{{ route('admin.integrasi') }}">🔗 Integrasi Pusat</a></div>
<form method="post" action="{{ route('admin.pengaturan.simpan') }}" class="card">@csrf @method('PUT')
    <h2>Absen mandiri</h2>
    <label class="chk"><input type="checkbox" name="aktif" value="1" @checked(old('aktif', $cfg['aktif']))> Izinkan guru &amp; staf absen masuk/pulang sendiri dari HP</label>
    <div class="fields" style="margin-top:12px">
        <div><label for="jm">Batas jam masuk</label><input id="jm" type="time" name="jam_masuk" value="{{ old('jam_masuk', $cfg['batas']) }}" required><small class="mut">Absen setelah jam ini ditandai terlambat.</small></div>
        <div><label for="lat">Lintang sekolah <small>(opsional)</small></label><input id="lat" type="text" name="lat" inputmode="decimal" value="{{ old('lat', $cfg['lat']) }}" placeholder="mis. -8.1567"></div>
        <div><label for="lng">Bujur sekolah <small>(opsional)</small></label><input id="lng" type="text" name="lng" inputmode="decimal" value="{{ old('lng', $cfg['lng']) }}" placeholder="mis. 113.7643"></div>
        <div><label for="rad">Radius (meter)</label><input id="rad" type="number" name="radius" min="0" max="5000" value="{{ old('radius', $cfg['radius']) }}"><small class="mut">0 = lokasi tidak diperiksa.</small></div>
    </div>
    <p class="hint">Koordinat sekolah: buka Google Maps, tekan lama titik sekolah, salin dua angka yang muncul. Bila radius diisi, absen mandiri hanya bisa dilakukan dalam jarak itu (GPS HP harus diizinkan).</p>
    <button class="btn btn-pri">Simpan</button>
</form>
<div class="card"><h2>Siapa yang bisa absen mandiri?</h2>
<p class="mut">Akun yang di Pusat diberi peran <b>pegawai</b> (atau peran lain) untuk aplikasi Tata Usaha <b>dan</b> tertaut ke data guru/staf. Daftar guru &amp; staf yang diabsen diambil dari Pusat (hanya yang aktif).</p></div>
@endsection
