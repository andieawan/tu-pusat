@extends('layouts.app', ['title' => 'Integrasi'])
@section('content')
<div class="head"><div><h1>Integrasi</h1><small>Sambungan ke Pusat Sekolah: akun, data induk (kelas, siswa, guru & staf), dan mutasi siswa.</small></div></div>
@include('admin._pusat')
<div class="card"><h2>Aplikasi PKL (nomor surat)</h2>
    <div class="scroll"><table class="tbl" style="font-size:14px">
        <tr><td style="width:190px" class="mut">Status</td><td>@if($apiPkl)<span class="badge b-ok">Kunci PKL terisi</span>@else<span class="badge b-warn">Belum tersambung — klik Sambungkan pada "PKL ↔ TU" di Pusat → Administrasi → Sambungan</span>@endif</td></tr>
        <tr><td class="mut">Kegunaan</td><td>Surat pengantar & penarikan PKL mengambil nomor dari Register Surat Keluar (kode <b>PKL</b>) dan tercatat di sini. Kunci diatur otomatis dari Pusat → Administrasi → Sambungan.</td></tr>
        <tr><td class="mut">Surat PKL tercatat</td><td>{{ $suratPkl }}</td></tr>
    </table></div>
</div>
@endsection
