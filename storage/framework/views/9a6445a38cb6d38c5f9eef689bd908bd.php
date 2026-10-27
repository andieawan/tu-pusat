<?php $__env->startSection('content'); ?>
<div class="card noprint">
    <?php if (! empty(trim($__env->yieldContent('toolbar')))): ?>
        <form method="get" class="fields"><?php echo $__env->yieldContent('toolbar'); ?>
            <div class="row"><button class="btn">Perbarui</button><button type="button" class="btn btn-pri" onclick="window.print()">Cetak / Simpan PDF</button></div>
        </form>
        <small>Alamat dan NIP tidak diisi otomatis; lengkapi manual bila diperlukan.</small>
    <?php else: ?>
        <button type="button" class="btn btn-pri" onclick="window.print()">Cetak / Simpan PDF</button>
    <?php endif; ?>
</div>
<article class="letter"><?php echo $__env->yieldContent('letter'); ?></article>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => $title], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/surat/layout.blade.php ENDPATH**/ ?>