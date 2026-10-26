<?php $__env->startSection('content'); ?>
<div class="head"><div><h1>🕘 Absen Saya</h1><small>Absensi mandiri guru &amp; staf — catat kehadiran Anda dari HP.</small></div></div>
<?php if(! $tertaut): ?>
    <div class="alert alert-warn"><span class="alert-ic" aria-hidden="true">!</span><span class="alert-txt">Akun Anda belum tertaut ke data guru/staf. Minta admin Pusat menautkan akun Anda ke data pegawai Anda, lalu keluar dan masuk lagi.</span></div>
<?php elseif(! $state): ?>
    <div class="alert alert-info"><span class="alert-ic" aria-hidden="true">i</span><span class="alert-txt">Absen mandiri sedang dimatikan oleh sekolah. Kehadiran Anda dicatat oleh TU.</span></div>
<?php else: ?>
    <?php echo $__env->make('partials.absen-card', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>

<?php if($history->isNotEmpty()): ?>
<div class="card card-tight">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line2)"><h2 style="margin:0">Riwayat 30 Hari Terakhir</h2></div>
    <?php $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="stu"><span class="nm"><b><?php echo e(\App\Support\Dates::human($h->tanggal)); ?></b>
            <small><?php if($h->jam_masuk): ?>Masuk <?php echo e($h->jam_masuk); ?><?php if($h->jam_pulang): ?> · Pulang <?php echo e($h->jam_pulang); ?><?php endif; ?> <?php if($h->terlambat): ?> · terlambat <?php endif; ?>
                <?php else: ?><?php echo e($h->catatan ?: ($h->sumber === 'mandiri' ? 'Absen mandiri' : 'Dicatat TU')); ?><?php endif; ?></small></span>
            <span class="lg <?php echo e($h->status); ?>" title="<?php echo e(\App\Services\AbsenService::STATUS[$h->status]); ?>"><?php echo e($h->status); ?></span></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Absen Saya'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/absen-saya.blade.php ENDPATH**/ ?>