<?php
    $s = $surat->siswa; $x = fn ($k, $d = '........................') => trim($surat->x($k)) !== '' ? $surat->x($k) : $d;
    $tgl = fn ($ymd) => \App\Support\Dates::long($ymd);
?>
<?php $__env->startSection('toolbar'); ?>
    <label>Tempat surat<input type="text" name="tempat" value="<?php echo e($tempat ?? ''); ?>" placeholder="mis. Pakusari"></label>
    <label>NIP Kepala Sekolah<input type="text" name="nip" value="<?php echo e($nip ?? ''); ?>" placeholder="opsional"></label>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('letter'); ?>
<?php echo $__env->make('surat._kop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php ($judul = $surat->jenis === 'biasa' ? $surat->perihal : mb_strtoupper(\App\Services\TuService::KETERANGAN[$surat->jenis])); ?>
<p style="text-align:center;margin-top:14px"><b><u><?php echo e(mb_strtoupper($judul)); ?></u></b><br>Nomor: <?php echo e($surat->nomor ?: '........................'); ?></p>
<?php if($surat->jenis === 'biasa'): ?>
    <?php if($surat->pihak): ?><p>Kepada Yth.<br><?php echo e($surat->pihak); ?></p><?php endif; ?>
    <p style="white-space:pre-line"><?php echo e($surat->isi ?: '........................................'); ?></p>
<?php else: ?>
    <p>Yang bertanda tangan di bawah ini, Kepala <?php echo e(config('tu.school_name')); ?>, menerangkan bahwa:</p>
    <table style="margin-left:24px">
        <tr><td>Nama</td><td>:</td><td><b><?php echo e($s->nama ?? '-'); ?></b></td></tr>
        <tr><td>NIS</td><td>:</td><td><?php echo e($s->nis ?? '-'); ?></td></tr>
        <tr><td>Jenis kelamin</td><td>:</td><td><?php echo e(($s->jk ?? '') === 'P' ? 'Perempuan' : 'Laki-laki'); ?></td></tr>
        <tr><td>Kelas</td><td>:</td><td><?php echo e($s->kelas->nama ?? '-'); ?></td></tr>
    </table>
    <?php if($surat->jenis === 'aktif'): ?>
        <p>adalah benar siswa aktif pada <?php echo e(config('tu.school_name')); ?> pada Tahun Ajaran <?php echo e($tahun ?: '....../......'); ?>.</p>
        <p>Surat keterangan ini dibuat untuk keperluan <?php echo e($x('keperluan')); ?>.</p>
    <?php elseif($surat->jenis === 'pindah'): ?>
        <p>adalah siswa <?php echo e(config('tu.school_name')); ?> yang mengajukan pindah ke <b><?php echo e($x('sekolah_tujuan')); ?></b> terhitung mulai tanggal <?php echo e($tgl($surat->tanggal)); ?><?php if($surat->x('alasan')): ?>, dengan alasan <?php echo e($surat->x('alasan')); ?><?php endif; ?>.</p>
        <p>Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    <?php else: ?>
        <?php ($mulai = $surat->x('mulai')); ?> <?php ($sampai = $surat->x('sampai') ?: $mulai); ?>
        <p>diberikan <?php echo e($surat->jenis === 'dispensasi' ? 'dispensasi' : 'izin'); ?> untuk tidak mengikuti kegiatan belajar mengajar pada <?php echo e($mulai === $sampai ? 'tanggal '.$tgl($mulai) : 'tanggal '.$tgl($mulai).' s.d. '.$tgl($sampai)); ?>, dengan alasan <?php echo e($x('alasan')); ?>.</p>
        <p>Demikian surat ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
    <?php endif; ?>
<?php endif; ?>
<p style="text-align:right;margin-top:28px"><?php echo e(($tempat ?? '') !== '' ? $tempat : '........................'); ?>, <?php echo e($tgl($surat->tanggal)); ?></p>
<div class="ttd" style="justify-content:flex-end"><div>Kepala Sekolah,<div class="sp"></div><b><u><?php echo e($cfg['kepsek'] ?: '(........................)'); ?></u></b><br>NIP. <?php echo e(($nip ?? '') !== '' ? $nip : '....................'); ?></div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('surat.layout', ['title' => $surat->perihal], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/tu/cetak-surat.blade.php ENDPATH**/ ?>