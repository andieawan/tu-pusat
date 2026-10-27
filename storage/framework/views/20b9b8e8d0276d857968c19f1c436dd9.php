<?php $__env->startSection('content'); ?>
<?php if($print): ?>
    <?php $__env->startPush('head'); ?><style>body.bare{background:#fff}@media print{.no-print{display:none!important}}</style><?php $__env->stopPush(); ?>
    <div class="no-print row" style="margin-bottom:12px"><a class="btn btn-sm" href="<?php echo e(route('tu.absen.rekap', ['bulan' => $bulan])); ?>">‹ Kembali</a><button type="button" class="btn btn-sm btn-pri" onclick="window.print()">Cetak / Simpan PDF</button></div>
    <div style="text-align:center;margin-bottom:14px"><h1 style="margin:0">REKAP KEHADIRAN GURU &amp; STAF</h1><div><?php echo e(config('tu.school_name')); ?></div><div>Bulan <?php echo e($label); ?></div></div>
<?php else: ?>
    <div class="head"><div><h1><?php echo e('Rekap Absensi Guru & Staf'); ?></h1><small><?php echo e('Rekap kehadiran bulan '.$label.'.'); ?></small></div></div>
    <form method="get" action="<?php echo e(route('tu.absen.rekap')); ?>" class="card filter-card"><div class="fields">
        <div><label for="bulan">Bulan</label><input id="bulan" type="month" name="bulan" value="<?php echo e($bulan); ?>" onchange="this.form.submit()"></div>
        <div style="align-self:end" class="row"><a class="btn btn-sm" target="_blank" href="<?php echo e(route('tu.absen.rekap', ['bulan' => $bulan, 'cetak' => 1])); ?>">Cetak</a><a class="btn btn-sm btn-pri" href="<?php echo e(route('tu.absen.unduh', ['bulan' => $bulan])); ?>">Unduh Excel</a></div>
        <noscript><div><button class="btn">Tampilkan</button></div></noscript>
    </div></form>
<?php endif; ?>
<div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['card card-tight' => ! $print]); ?>">
    <?php if(! $rows): ?><div class="empty">Belum ada guru/staf aktif.</div><?php else: ?>
    <div class="scroll"><table class="<?php echo \Illuminate\Support\Arr::toCssClasses(['tbl', 'tbl-cards' => ! $print]); ?>" <?php if($print): ?> border="1" style="border-collapse:collapse" <?php endif; ?>>
        <thead><tr><th class="c">No</th><th>Nama</th><th>Jabatan</th><th class="c">H</th><th class="c">I</th><th class="c">S</th><th class="c">A</th><th class="c">D</th><th class="c">Terlambat</th><th class="c">Hari tercatat</th><th class="c">% Hadir</th></tr></thead>
        <tbody>
        <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td class="c mut"><?php echo e($i + 1); ?></td><td class="card-title"><b><?php echo e($r['pegawai']->nama); ?></b></td><td data-label="Jabatan"><?php echo e($r['pegawai']->label()); ?></td>
                <td class="c mono" data-label="Hadir"><?php echo e($r['H']); ?></td><td class="c mono" data-label="Izin"><?php echo e($r['I']); ?></td><td class="c mono warn" data-label="Sakit"><?php echo e($r['S']); ?></td><td class="c mono bad" data-label="Alpa"><?php echo e($r['A']); ?></td><td class="c mono" data-label="Dinas luar"><?php echo e($r['D']); ?></td><td class="c mono warn" data-label="Terlambat"><?php echo e($r['T']); ?></td>
                <td class="c mono mut" data-label="Hari tercatat"><?php echo e($r['total']); ?></td><td class="c mono" data-label="% hadir"><?php if($r['rate'] === null): ?>–<?php else: ?><?php echo e($r['rate']); ?>%<?php endif; ?></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody></table></div><?php endif; ?>
</div>
<?php if($print): ?><p style="font-size:12px;margin-top:8px">H = Hadir, I = Izin, S = Sakit, A = Alpa, D = Dinas Luar (dihitung hadir pada persentase), Terlambat = absen masuk melewati batas jam (absen mandiri).</p>
<div style="display:flex;justify-content:flex-end;margin-top:36px"><div style="text-align:center;min-width:240px">Kepala Sekolah,<br><br><br><br><b><?php echo e($cfg['kepsek'] ?: '........................'); ?></b></div></div><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Rekap Absensi Guru & Staf', 'bare' => $print, 'width' => $print ? '900px' : null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/tu/absen-rekap.blade.php ENDPATH**/ ?>