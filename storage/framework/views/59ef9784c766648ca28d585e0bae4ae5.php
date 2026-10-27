<?php ($c = $cfg ?? config('tu.surat')); ?>
<header class="kop2">
    <img src="<?php echo e(asset('assets/logo-ibu.png')); ?>" alt="" width="84" height="84">
    <div>
        <div class="k1"><?php echo e($c['yayasan']); ?></div>
        <div class="k1"><?php echo e($c['yayasan2']); ?></div>
        <div class="k2"><?php echo e($c['nama']); ?></div>
        <div class="k3">NSS : <?php echo e($c['nss']); ?> &nbsp;&nbsp;&nbsp; NPSN : <?php echo e($c['npsn']); ?></div>
        <div class="k4"><?php echo e($c['bidang']); ?></div>
        <div class="k4"><?php echo e($c['alamat']); ?></div>
    </div>
</header>
<?php /**PATH /home/claude/tu-pusat/resources/views/surat/_kop.blade.php ENDPATH**/ ?>