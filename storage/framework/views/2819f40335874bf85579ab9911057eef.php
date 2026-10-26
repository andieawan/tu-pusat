<?php $__env->startSection('content'); ?>
<div class="head"><div><h1><?php echo e('Absensi Guru & Staf'); ?></h1><small><?php echo e(\App\Support\Dates::human($tanggal)); ?></small></div></div>
<?php
    $today = \App\Support\Dates::today();
    $shift = fn (int $d) => gmdate('Y-m-d', strtotime($tanggal.' UTC') + $d * 86400);
    $url = fn (array $o = []) => route('tu.absen', array_merge(['date' => $tanggal], $o));
    $can = $canWrite && ! $locked;
?>
<form method="get" action="<?php echo e(route('tu.absen')); ?>" class="card filter-card">
    <div class="fields"><div class="date-pick"><label for="date">Tanggal</label>
        <div class="date-row">
            <a class="btn btn-icon" href="<?php echo e($url(['date' => $shift(-1)])); ?>" aria-label="Hari sebelumnya">‹</a>
            <input id="date" type="date" name="date" value="<?php echo e($tanggal); ?>" max="<?php echo e($today); ?>" onchange="this.form.submit()">
            <?php if($tanggal < $today): ?><a class="btn btn-icon" href="<?php echo e($url(['date' => $shift(1)])); ?>" aria-label="Hari berikutnya">›</a><?php else: ?><span class="btn btn-icon" aria-disabled="true">›</span><?php endif; ?>
            <?php if($tanggal !== $today): ?><a class="btn btn-sm" href="<?php echo e($url(['date' => $today])); ?>">Hari ini</a><?php endif; ?>
        </div></div>
        <div style="align-self:end"><a class="btn btn-sm" href="<?php echo e(route('tu.absen.rekap', ['bulan' => substr($tanggal, 0, 7)])); ?>">Rekap bulanan</a></div>
    </div>
</form>

<?php if($locked && $canWrite): ?><div class="alert alert-warn"><span class="alert-ic" aria-hidden="true">!</span><span class="alert-txt">🔒 Absensi lebih dari 7 hari terkunci. Hanya Administrator yang dapat mengubah.</span></div><?php endif; ?>
<?php if($existing->isNotEmpty()): ?><div class="alert alert-info"><span class="alert-ic" aria-hidden="true">i</span><span class="alert-txt"><b>Sudah diisi</b> untuk tanggal ini. Menyimpan lagi hanya memperbarui.</span></div><?php endif; ?>

<?php if($pegawai->isEmpty()): ?>
    <div class="card"><div class="empty">Belum ada data guru/staf aktif. Data guru & staf diambil dari Pusat: isi di Pusat lalu Admin → Integrasi → Sinkron sekarang.</div></div>
<?php elseif($can): ?>
<form method="post" action="<?php echo e(route('tu.absen.simpan')); ?>" class="card card-tight" data-unsaved><?php echo csrf_field(); ?> <input type="hidden" name="date" value="<?php echo e($tanggal); ?>">
    <div class="list-tools">
        <button type="button" class="btn btn-sm" data-setall="H">✓ Tandai semua Hadir</button>
        <span class="legend" aria-hidden="true"><i class="lg H">H</i>Hadir <i class="lg I">I</i>Izin <i class="lg S">S</i>Sakit <i class="lg A">A</i>Alpa <i class="lg D">D</i>Dinas</span>
    </div>
    <?php $__currentLoopData = $pegawai; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php ($cur = $existing[$u->id] ?? null); ?>
        <?php ($st = old("status.{$u->id}", $cur->status ?? 'H')); ?>
        <?php ($note = old("notes.{$u->id}", $cur->catatan ?? '')); ?>
        <div class="stu stu-att" data-row>
            <span class="no mono"><?php echo e($i + 1); ?></span>
            <div class="nm"><b><?php echo e($u->nama); ?></b><small><?php echo e($u->label()); ?><?php echo $__env->make('tu._jam', ['cur' => $cur], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></small></div>
            <div class="hisa" role="radiogroup" aria-label="Status kehadiran <?php echo e($u->nama); ?>">
                <?php $__currentLoopData = \App\Services\AbsenService::STATUS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $lbl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label title="<?php echo e($lbl); ?>"><input type="radio" name="status[<?php echo e($u->id); ?>]" value="<?php echo e($k); ?>" data-status="<?php echo e($k); ?>" <?php if($st === $k): echo 'checked'; endif; ?> aria-label="<?php echo e($lbl); ?>"><span class="<?php echo e($k); ?>" aria-hidden="true"><?php echo e($k); ?></span></label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <button type="button" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['note-toggle', 'has-note' => $note !== '']); ?>" data-note-toggle aria-expanded="<?php echo e($note !== '' ? 'true' : 'false'); ?>" aria-controls="snote-<?php echo e($u->id); ?>" title="Catatan">✎<span class="sr">Catatan untuk <?php echo e($u->nama); ?></span></button>
            <input id="snote-<?php echo e($u->id); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['note', 'note-open' => $note !== '']); ?>" type="text" name="notes[<?php echo e($u->id); ?>]" value="<?php echo e($note); ?>" placeholder="Catatan, mis. surat dokter / tugas dinas" maxlength="200" aria-label="Catatan <?php echo e($u->nama); ?>">
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <div class="sticky-save">
        <div id="recount" class="recount" aria-live="polite"><span class="rc H">H <b data-c="H">0</b></span><span class="rc I">I <b data-c="I">0</b></span><span class="rc S">S <b data-c="S">0</b></span><span class="rc A">A <b data-c="A">0</b></span><span class="rc D">D <b data-c="D">0</b></span></div>
        <button class="btn btn-pri" data-busy="Menyimpan…">Simpan Absensi</button>
    </div>
</form>
<?php else: ?>
<div class="card card-tight">
    <?php $__currentLoopData = $pegawai; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php ($cur = $existing[$u->id] ?? null); ?>
        <div class="stu"><span class="no mono"><?php echo e($i + 1); ?></span><div class="nm"><b><?php echo e($u->nama); ?></b><small><?php echo e($u->label()); ?><?php echo $__env->make('tu._jam', ['cur' => $cur], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php if($cur?->catatan): ?> · <?php echo e($cur->catatan); ?><?php endif; ?></small></div>
            <?php if($cur): ?><span class="lg <?php echo e($cur->status); ?>" title="<?php echo e(\App\Services\AbsenService::STATUS[$cur->status]); ?>"><?php echo e($cur->status); ?></span><?php else: ?><span class="badge">Belum diisi</span><?php endif; ?></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Absensi Guru & Staf'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/tu/absen.blade.php ENDPATH**/ ?>