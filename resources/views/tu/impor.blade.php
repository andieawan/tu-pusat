@extends('layouts.app', ['title' => 'Impor register surat lama'])
@section('content')
<div class="head"><div><h1>Impor Register Surat Lama</h1><small>Memasukkan buku agenda surat masuk &amp; keluar sebelum aplikasi dipakai.</small></div></div>

@if(session('galat'))
    <div class="card"><h2>Baris yang perlu diperbaiki</h2><ul class="galat">@foreach(session('galat') as $g)<li>{{ $g }}</li>@endforeach</ul></div>
@endif

<div class="card">
    <h2>1. Unduh templat</h2>
    <p class="mut" style="margin:0 0 10px">Berisi lembar PETUNJUK, SURAT MASUK, dan SURAT KELUAR. Satu baris = satu surat.</p>
    <a class="btn" href="{{ route('tu.impor.templat') }}">Unduh templat (.xlsx)</a>
    <div class="scroll" style="margin-top:12px"><table class="tbl">
        <thead><tr><th>Lembar</th><th>Kolom</th></tr></thead>
        <tbody>@foreach(\App\Services\ImporSurat::LEMBAR as $arah => $lembar)<tr><td><b>{{ $lembar }}</b></td><td>{{ implode(' · ', \App\Services\ImporSurat::KOLOM[$arah]) }}</td></tr>@endforeach</tbody>
    </table></div>
</div>

<div class="card">
    <h2>2. Isi lalu unggah</h2>
    <ul>
        <li>No. Agenda boleh diisi sesuai buku lama (unik per tahun); kosong = nomor berikutnya.</li>
        <li>Nomor surat ditulis apa adanya — surat lama tidak dibuatkan nomor baru. Status surat masuk kosong = <b>Selesai</b>.</li>
        <li>Surat yang sama (tanggal, pihak, perihal) yang sudah ada dilewati. Bila ada satu baris salah, tidak ada yang disimpan.</li>
    </ul>
    <form method="post" action="{{ route('tu.impor.unggah') }}" enctype="multipart/form-data" class="fields" style="margin-top:10px">
        @csrf
        <div style="grid-column:span 2"><label for="berkas">Berkas templat (.xlsx)</label><input id="berkas" type="file" name="berkas" accept=".xlsx" required></div>
        <div class="row" style="align-self:end"><button class="btn" name="uji" value="1">Periksa saja</button><button class="btn btn-pri" name="uji" value="0">Impor</button></div>
    </form>
</div>
@endsection
