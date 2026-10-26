<?php $__env->startSection('content'); ?>
<div class="head"><div><h1>Integrasi</h1><small>Sambungan ke Pusat Sekolah: akun, data induk (kelas, siswa, guru & staf), dan mutasi siswa.</small></div></div>
<?php if($perluSinkron): ?><div class="alert alert-warn"><span class="alert-txt">Ada perubahan dari Pusat yang belum lengkap (mis. siswa dipindah ke kelas baru yang belum tersalin). Klik <b>Sinkron sekarang</b>; bila tidak, sinkron berjalan otomatis dalam satu jam.</span></div><?php endif; ?>
<div class="card"><h2>Pusat Sekolah</h2>
    <p>Status: <?php if($pusatAktif && $siap): ?><span class="badge b-ok">Aktif</span><?php elseif($pusatAktif): ?><span class="badge b-warn">Aktif, database belum diperbarui</span><?php else: ?><span class="badge">Nonaktif (PUSAT_ENABLED=false)</span><?php endif; ?></p>
    <table class="kv"><tr><td>Alamat Pusat</td><td><?php echo e($pusatUrl ?: '-'); ?></td></tr>
    <tr><td>Redirect URI</td><td><code><?php echo e($callback); ?></code></td></tr>
    <tr><td>URL webhook</td><td><code><?php echo e($webhook); ?></code></td></tr>
    <tr><td>Tahun ajaran aktif</td><td><?php echo e($tahun ?: '-'); ?></td></tr>
    <tr><td>Sinkron terakhir</td><td><?php echo e($terakhir ?: '-'); ?></td></tr>
    <tr><td>Data tersalin</td><td><?php echo e($jumlah['kelas']); ?> kelas · <?php echo e($jumlah['siswa']); ?> siswa aktif · <?php echo e($jumlah['pegawai']); ?> guru &amp; staf aktif</td></tr></table>
    <div class="row" style="margin-top:10px">
        <form method="post" action="<?php echo e(route('admin.integrasi.pusat')); ?>"><?php echo csrf_field(); ?><button class="btn">Uji koneksi</button></form>
        <form method="post" action="<?php echo e(route('admin.integrasi.sinkron')); ?>"><?php echo csrf_field(); ?><button class="btn btn-pri" data-busy="Menyinkron…">Sinkron sekarang</button></form>
    </div>
    <?php if(session('hasil')): ?><pre class="mono" style="font-size:12px;margin-top:10px;white-space:pre-wrap"><?php echo e(json_encode(session('hasil'), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); ?></pre><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Integrasi'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/admin/integrasi.blade.php ENDPATH**/ ?>