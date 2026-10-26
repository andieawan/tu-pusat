<?php

use Illuminate\Support\Facades\Schedule;

// Cron wajib di VM: * * * * * cd /var/www/tu-pusat && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('tu:bersihkan')->dailyAt('02:30');
// Sinkron penuh tiap jam bila dihidupkan, atau bila webhook menandai ada data yang perlu dilengkapi (kelas belum dikenal).
Schedule::command('tu:sinkron')->hourly()->withoutOverlapping(30)->when(function () {
    try {
        return (bool) config('pusat.sinkron_otomatis') || \App\Services\PusatKlien::baca('perlu_sinkron') !== null;
    } catch (\Throwable) {
        return false;
    }
});
