<?php $__env->startSection('content'); ?>
<?php ($w = auth()->user()->bisaUbah()); ?>
<div class="head"><div><h1><?php echo e('Tata Usaha'); ?></h1><small><?php echo e($w ? 'Administrasi siswa, surat, dan kehadiran guru & staf.' : 'Ringkasan administrasi (baca saja).'); ?></small></div></div>
<?php echo $__env->make('partials.absen-card', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="kpis kpis-4">
    <div class="kpi"><small>Siswa aktif</small><div class="n"><?php echo e($siswaAktif); ?></div></div>
    <div class="kpi"><small>Mutasi bulan ini</small><div class="n"><?php echo e($mutasiMasuk + $mutasiKeluar); ?></div><small><?php echo e($mutasiMasuk); ?> masuk · <?php echo e($mutasiKeluar); ?> keluar</small></div>
    <div class="kpi"><small>Surat masuk belum selesai</small><div class="n <?php echo e($masukBelum ? 'warn' : 'ok'); ?>"><?php echo e($masukBelum); ?></div></div>
    <div class="kpi"><small>Absen guru hari ini</small><div class="n <?php echo e($absenHariIni ? 'ok' : 'bad'); ?>"><?php echo e($absenHariIni ? 'Sudah' : 'Belum'); ?></div><small><?php echo e($pegawai); ?> guru &amp; staf</small></div>
</div>

<div class="quick">
    <?php if($w): ?><a class="quick-item primary" href="<?php echo e(route('tu.absen')); ?>"><span class="bk-ic" aria-hidden="true">🗓</span><span><b>Absensi Guru &amp; Staf</b><small>Isi kehadiran hari ini</small></span></a><?php endif; ?>
    <a class="quick-item" href="<?php echo e(route('tu.mutasi')); ?>"><span class="bk-ic" aria-hidden="true">🔁</span><span><b>Mutasi Siswa</b><small>Masuk &amp; keluar</small></span></a>
    <a class="quick-item" href="<?php echo e(route('tu.surat', 'keluar')); ?>"><span class="bk-ic" aria-hidden="true">📤</span><span><b>Surat Keluar</b><small><?php echo e($keluarBulan); ?> surat bulan ini</small></span></a>
    <a class="quick-item" href="<?php echo e(route('tu.surat', 'masuk')); ?>"><span class="bk-ic" aria-hidden="true">📥</span><span><b>Surat Masuk</b><small>Register &amp; disposisi</small></span></a>
    <a class="quick-item" href="<?php echo e(route('tu.cetak')); ?>"><span class="bk-ic" aria-hidden="true">🖨</span><span><b>Cetak Dokumen</b><small>Daftar hadir, kartu siswa</small></span></a>
</div>

<div class="card card-tight">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line2)"><h2>Surat Terbaru</h2></div>
    <?php $__empty_1 = true; $__currentLoopData = $terbaru; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <a class="stu" style="color:inherit" href="<?php echo e(route('tu.surat', $s->arah)); ?>"><span aria-hidden="true"><?php echo e($s->arah === 'masuk' ? '📥' : '📤'); ?></span>
            <span class="nm"><b><?php echo e($s->perihal); ?></b><small><?php echo e($s->nomor ?: 'Agenda '.$s->urut.'/'.$s->tahun); ?> · <?php echo e(\App\Support\Dates::human($s->tanggal)); ?></small></span></a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty">Belum ada surat tercatat.</div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Tata Usaha'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/beranda.blade.php ENDPATH**/ ?>