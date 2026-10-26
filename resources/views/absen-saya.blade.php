@extends('layouts.app', ['title' => 'Absen Saya'])
@section('content')
<div class="head"><div><h1>🕘 Absen Saya</h1><small>Absensi mandiri guru &amp; staf — catat kehadiran Anda dari HP.</small></div></div>
@if(! $tertaut)
    <div class="alert alert-warn"><span class="alert-ic" aria-hidden="true">!</span><span class="alert-txt">Akun Anda belum tertaut ke data guru/staf. Minta admin Pusat menautkan akun Anda ke data pegawai Anda, lalu keluar dan masuk lagi.</span></div>
@elseif(! $state)
    <div class="alert alert-info"><span class="alert-ic" aria-hidden="true">i</span><span class="alert-txt">Absen mandiri sedang dimatikan oleh sekolah. Kehadiran Anda dicatat oleh TU.</span></div>
@else
    @include('partials.absen-card')
@endif

@if($history->isNotEmpty())
<div class="card card-tight">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line2)"><h2 style="margin:0">Riwayat 30 Hari Terakhir</h2></div>
    @foreach($history as $h)
        <div class="stu"><span class="nm"><b>{{ \App\Support\Dates::human($h->tanggal) }}</b>
            <small>@if($h->jam_masuk)Masuk {{ $h->jam_masuk }}@if($h->jam_pulang) · Pulang {{ $h->jam_pulang }}@endif @if($h->terlambat) · terlambat @endif
                @else{{ $h->catatan ?: ($h->sumber === 'mandiri' ? 'Absen mandiri' : 'Dicatat TU') }}@endif</small></span>
            <span class="lg {{ $h->status }}" title="{{ \App\Services\AbsenService::STATUS[$h->status] }}">{{ $h->status }}</span></div>
    @endforeach
</div>
@endif
@endsection
