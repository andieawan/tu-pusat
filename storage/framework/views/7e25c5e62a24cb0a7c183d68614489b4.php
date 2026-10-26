
<?php
    $sc = \App\Services\AbsenService::state(auth()->user());
?>
<?php if($sc): ?>
<?php
    $row = $sc['row']; $cfg = $sc['cfg']; $geo = $cfg['radius'] > 0;
    $lbl = \App\Services\AbsenService::STATUS;
?>
<section class="card selfcheck" aria-labelledby="sc-h">
    <div class="sc-top">
        <div><h2 id="sc-h" style="margin:0">Absen Hari Ini</h2><small><?php echo e(\App\Support\Dates::long($sc['today'])); ?> · pukul <?php echo e($sc['now']); ?> WIB</small></div>
        <?php if($row): ?>
            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['badge', 'b-ok' => $row->status === 'H' && ! $row->terlambat, 'b-warn' => ($row->status === 'H' && $row->terlambat) || in_array($row->status, ['I', 'S', 'D']), 'b-bad' => $row->status === 'A']); ?>"><?php echo e($lbl[$row->status]); ?><?php if($row->terlambat): ?> · Terlambat <?php endif; ?></span>
        <?php else: ?>
            <span class="badge">Belum absen</span>
        <?php endif; ?>
    </div>

    <?php if($row && $row->status === 'H' && $row->jam_masuk): ?>
        <p class="sc-times"><span>Masuk <b class="mono"><?php echo e($row->jam_masuk); ?></b></span><span>Pulang <b class="mono"><?php echo e($row->jam_pulang ?: '—'); ?></b></span></p>
    <?php elseif($row && $row->status !== 'H'): ?>
        <p class="mut" style="margin:8px 0 0"><?php echo e($row->catatan ?: 'Tercatat oleh '.($row->sumber === 'mandiri' ? 'Anda' : 'TU').'.'); ?></p>
    <?php endif; ?>

    <?php if(! $row || ($row->status === 'H' && ! $row->jam_masuk) || ($row->status === 'H' && $row->jam_masuk && ! $row->jam_pulang) || ($row->sumber === 'mandiri' && $row->status !== 'H' && ! $row->jam_masuk)): ?>
    <form method="post" action="<?php echo e(route('absen.saya.simpan')); ?>" data-geo="<?php echo e($geo ? 1 : 0); ?>" class="sc-form"><?php echo csrf_field(); ?>
        <input type="hidden" name="aksi" value=""><input type="hidden" name="lat" value=""><input type="hidden" name="lng" value="">
        <?php if(! $row || ($row->status === 'H' && ! $row->jam_masuk)): ?>
            <button class="btn btn-pri sc-big" name="aksi" value="masuk" data-busy="<?php echo e($geo ? 'Memeriksa lokasi…' : 'Mencatat…'); ?>">✅ Absen Masuk</button>
        <?php elseif($row->status === 'H' && ! $row->jam_pulang): ?>
            <button class="btn btn-dark sc-big" name="aksi" value="pulang" data-busy="<?php echo e($geo ? 'Memeriksa lokasi…' : 'Mencatat…'); ?>">🏠 Absen Pulang</button>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <?php if(! $row || ($row->sumber === 'mandiri' && $row->status !== 'H' && ! $row->jam_masuk)): ?>
    <details class="more" style="margin-top:10px"><summary>Tidak masuk hari ini? Lapor Izin / Sakit / Dinas Luar</summary>
        <form method="post" action="<?php echo e(route('absen.saya.simpan')); ?>" class="fields" style="margin-top:8px"><?php echo csrf_field(); ?> <input type="hidden" name="aksi" value="lapor">
            <div><label for="sc-st">Keterangan</label><select id="sc-st" name="status"><option value="I">Izin</option><option value="S">Sakit</option><option value="D">Dinas Luar</option></select></div>
            <div style="grid-column:span 2"><label for="sc-note">Alasan singkat</label><input id="sc-note" type="text" name="catatan" maxlength="200" placeholder="mis. keperluan keluarga / surat dokter / tugas dinas" required></div>
            <div style="align-self:end"><button class="btn">Kirim Laporan</button></div>
        </form>
    </details>
    <?php endif; ?>

    <small class="mut" style="display:block;margin-top:10px">
        Batas jam masuk <b><?php echo e($cfg['batas']); ?></b>.
        <?php if($geo): ?>📍 Lokasi Anda diperiksa (harus dalam radius <?php echo e($cfg['radius']); ?> m dari sekolah) — izinkan akses lokasi saat diminta.<?php endif; ?>
        <?php if($row && $row->jarak !== null): ?> Jarak saat masuk: <?php echo e($row->jarak); ?> m.<?php endif; ?>
    </small>
</section>
<?php endif; ?>
<?php /**PATH /home/claude/tu-pusat/resources/views/partials/absen-card.blade.php ENDPATH**/ ?>