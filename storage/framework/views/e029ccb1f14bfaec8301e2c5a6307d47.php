
<?php ($v = fn (string $k, $d = '') => $rec ? ($rec->{$k} ?? $d) : old($k, $d)); ?>
<div class="fields">
    <div><label for="<?php echo e($px); ?>tgl"><?php echo e($arah === 'masuk' ? 'Tanggal diterima' : 'Tanggal surat'); ?></label><input id="<?php echo e($px); ?>tgl" type="date" name="tanggal" value="<?php echo e($v('tanggal', $today)); ?>" required></div>
    <div><label for="<?php echo e($px); ?>no"><?php echo e($arah === 'masuk' ? 'Nomor surat (dari pengirim)' : 'Nomor surat'); ?> <?php if($arah === 'keluar'): ?><small>(kosongkan = otomatis)</small><?php endif; ?></label><input id="<?php echo e($px); ?>no" type="text" name="nomor" value="<?php echo e($rec ? $rec->nomor : old('nomor')); ?>" maxlength="80"></div>
    <div><label for="<?php echo e($px); ?>ph"><?php echo e($arah === 'masuk' ? 'Pengirim' : 'Tujuan'); ?></label><input id="<?php echo e($px); ?>ph" type="text" name="pihak" value="<?php echo e($v('pihak')); ?>" maxlength="191" required></div>
    <?php if($arah === 'masuk'): ?>
        <div><label for="<?php echo e($px); ?>st">Status</label><select id="<?php echo e($px); ?>st" name="status"><?php $__currentLoopData = \App\Services\TuService::STATUS_MASUK; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if($v('status', 'Baru') === $o): echo 'selected'; endif; ?>><?php echo e($o); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
    <?php endif; ?>
</div>
<div class="field" style="margin-top:12px"><label for="<?php echo e($px); ?>pf">Perihal</label><input id="<?php echo e($px); ?>pf" type="text" name="perihal" value="<?php echo e($v('perihal')); ?>" maxlength="191" required></div>
<div class="field"><label for="<?php echo e($px); ?>is"><?php echo e($arah === 'masuk' ? 'Ringkasan isi' : 'Isi surat'); ?> <small>(opsional<?php echo e($arah === 'keluar' ? '; dipakai saat surat dicetak' : ''); ?>)</small></label><textarea id="<?php echo e($px); ?>is" name="isi" rows="3"><?php echo e($v('isi')); ?></textarea></div>
<?php if($arah === 'masuk'): ?>
    <div class="field"><label for="<?php echo e($px); ?>ds">Disposisi <small>(opsional)</small></label><input id="<?php echo e($px); ?>ds" type="text" name="disposisi" value="<?php echo e($v('disposisi')); ?>" maxlength="191" placeholder="mis. Kepsek → Waka Kurikulum"></div>
<?php endif; ?>
<div class="field"><label for="<?php echo e($px); ?>bk">Scan / foto surat <small>(opsional, JPG/PNG/PDF maks. 8 MB)</small></label>
    <div class="row"><input id="<?php echo e($px); ?>bk" type="file" name="berkas" accept="image/*,application/pdf" style="flex:1;min-width:180px"><button type="button" class="btn btn-sm" data-camera="#<?php echo e($px); ?>bk">📷 Foto dengan kamera</button></div>
    <?php if($rec?->berkas_path): ?><small class="mut">Sudah ada berkas — pilih berkas baru untuk mengganti.</small><?php endif; ?></div>
<?php /**PATH /home/claude/tu-pusat/resources/views/tu/_surat-fields.blade.php ENDPATH**/ ?>