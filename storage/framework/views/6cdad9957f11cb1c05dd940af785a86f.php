
<?php
    $pusatUrl = rtrim((string) config('pusat.url'), '/');
    $lain = $pusatUrl === '' ? [] : array_values(array_filter((array) session('aplikasi_lain', []),
        fn ($a) => is_array($a) && isset($a['nama'], $a['url']) && str_starts_with((string) $a['url'], $pusatUrl.'/')));
?>
<?php if($lain): ?>
    <div class="acct-head" style="padding-top:8px;padding-bottom:4px"><small>Pindah ke aplikasi lain</small></div>
    <?php $__currentLoopData = $lain; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a role="menuitem" href="<?php echo e($a['url']); ?>">↗ <?php echo e($a['nama']); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php if($pusatUrl !== ''): ?><a role="menuitem" href="<?php echo e($pusatUrl); ?>/portal">🏫 Pusat Sekolah</a><?php endif; ?>
<?php /**PATH /home/claude/tu-pusat/resources/views/partials/aplikasi-lain.blade.php ENDPATH**/ ?>