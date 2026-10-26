<?php $__env->startSection('content'); ?>
<div class="head"><div><h1><?php echo e($arah === 'masuk' ? 'Register Surat Masuk' : 'Register Surat Keluar'); ?></h1><small><?php echo e($arah === 'masuk' ? 'Catat surat yang diterima sekolah, disposisi, dan scan-nya.' : 'Nomor surat dibuat otomatis (contoh 012/KET/'.config('tu.kode_surat').'/X/'.substr($today, 0, 4).').'); ?></small></div></div>

<?php if($canWrite): ?>
<div class="grid g2" style="align-items:start">
    <details class="card" <?php if($errors->any() || session('error')): ?> open <?php endif; ?>><summary style="font-weight:700">+ Catat Surat <?php echo e($arah === 'masuk' ? 'Masuk' : 'Keluar (biasa)'); ?></summary>
        <form method="post" action="<?php echo e(route('tu.surat.simpan', $arah)); ?>" enctype="multipart/form-data" style="margin-top:12px"><?php echo csrf_field(); ?>
            <?php echo $__env->make('tu._surat-fields', ['rec' => null, 'px' => 'n-'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <button class="btn btn-pri" data-busy="Menyimpan…">Simpan</button>
        </form>
    </details>
    <?php if($arah === 'keluar'): ?>
    <details class="card"><summary style="font-weight:700">✉ Buat Surat Keterangan / Dispensasi / Izin Siswa</summary>
        <form method="post" action="<?php echo e(route('tu.keterangan')); ?>" style="margin-top:12px" data-jenis-form><?php echo csrf_field(); ?>
            <div class="fields">
                <div><label for="kt-jenis">Jenis surat</label><select id="kt-jenis" name="jenis" data-jenis-switch><?php $__currentLoopData = \App\Services\TuService::KETERANGAN; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if(old('jenis') === $k): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div><label for="kt-kelas">Kelas</label><select id="kt-kelas" data-pick-class="#kt-siswa"><option value="">— pilih kelas —</option><?php $__currentLoopData = $kelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(isset($pickSiswa[$c->id])): ?><option value="<?php echo e($c->id); ?>"><?php echo e($c->nama); ?></option><?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div style="grid-column:span 2"><label for="kt-siswa">Nama siswa</label><select id="kt-siswa" name="siswa_id" required><option value="">— pilih siswa —</option>
                    <?php $__currentLoopData = $kelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(isset($pickSiswa[$c->id])): ?><optgroup label="<?php echo e($c->nama); ?>" data-class="<?php echo e($c->id); ?>"><?php $__currentLoopData = $pickSiswa[$c->id]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s->id); ?>" <?php if((int) old('siswa_id') === $s->id): echo 'selected'; endif; ?>><?php echo e($s->nama); ?> (<?php echo e($s->nis); ?>)</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></optgroup><?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div><label for="kt-tgl">Tanggal surat</label><input id="kt-tgl" type="date" name="tanggal" value="<?php echo e(old('tanggal', $today)); ?>" required></div>
                <div data-jenis="aktif"><label for="kt-kep">Untuk keperluan</label><input id="kt-kep" type="text" name="keperluan" value="<?php echo e(old('keperluan')); ?>" placeholder="mis. pengajuan beasiswa"></div>
                <div data-jenis="pindah"><label for="kt-sek">Sekolah tujuan</label><input id="kt-sek" type="text" name="sekolah_tujuan" value="<?php echo e(old('sekolah_tujuan')); ?>"></div>
                <div data-jenis="dispensasi izin"><label for="kt-mulai">Mulai tanggal</label><input id="kt-mulai" type="date" name="mulai" value="<?php echo e(old('mulai', $today)); ?>"></div>
                <div data-jenis="dispensasi izin"><label for="kt-sampai">Sampai tanggal <small>(kosong = 1 hari)</small></label><input id="kt-sampai" type="date" name="sampai" value="<?php echo e(old('sampai')); ?>"></div>
                <div data-jenis="pindah dispensasi izin" style="grid-column:span 2"><label for="kt-al">Alasan</label><input id="kt-al" type="text" name="alasan" value="<?php echo e(old('alasan')); ?>" placeholder="mis. mengikuti lomba tingkat kabupaten"></div>
            </div>
            <button class="btn btn-pri" style="margin-top:12px" data-busy="Membuat…">Buat Surat &amp; Nomor Otomatis</button>
        </form>
    </details>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="get" action="<?php echo e(route('tu.surat', $arah)); ?>" class="card filter-card">
    <div class="fields">
        <div><label for="q">Cari</label><input id="q" type="search" name="q" value="<?php echo e($q); ?>" placeholder="Nomor / perihal / <?php echo e($arah === 'masuk' ? 'pengirim' : 'tujuan'); ?>"></div>
        <div><label for="th">Tahun</label><select id="th" name="tahun" data-auto><?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if($y === $tahun): echo 'selected'; endif; ?>><?php echo e($y); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <?php if($arah === 'masuk'): ?><div><label for="fs">Status</label><select id="fs" name="status" data-auto><option value="">Semua</option><?php $__currentLoopData = \App\Services\TuService::STATUS_MASUK; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if($status === $o): echo 'selected'; endif; ?>><?php echo e($o); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><?php endif; ?>
        <div style="align-self:end"><button class="btn">Cari</button></div>
    </div>
</form>

<div class="card card-tight" id="daftar-surat" data-swap>
    <?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <article class="bk-item">
            <div class="bk-top">
                <div class="nm"><b><?php echo e($s->perihal); ?></b>
                    <small>Agenda <span class="mono"><?php echo e($s->urut); ?>/<?php echo e($s->tahun); ?></span><?php if($s->nomor): ?> · No. <span class="mono"><?php echo e($s->nomor); ?></span><?php endif; ?> · <?php echo e(\App\Support\Dates::human($s->tanggal)); ?></small>
                    <small><?php echo e($arah === 'masuk' ? 'Dari' : 'Kepada'); ?>: <?php echo e($s->pihak); ?><?php if($s->siswa): ?> · siswa: <?php echo e($s->siswa->nama); ?><?php endif; ?></small></div>
                <div class="row" style="gap:6px">
                    <?php if($s->jenis !== 'biasa'): ?><span class="badge"><?php echo e(\App\Services\TuService::KETERANGAN[$s->jenis] ?? $s->jenis); ?></span><?php endif; ?>
                    <?php if($s->status): ?><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['badge', 'b-warn' => $s->status === 'Diproses', 'b-bad' => $s->status === 'Baru', 'b-ok' => $s->status === 'Selesai']); ?>"><?php echo e($s->status); ?></span><?php endif; ?>
                </div>
            </div>
            <?php if($s->isi && $s->jenis === 'biasa'): ?><p class="bk-txt"><?php echo e(\Illuminate\Support\Str::limit($s->isi, 220)); ?></p><?php endif; ?>
            <?php if($s->disposisi): ?><p class="bk-txt"><b>Disposisi:</b> <?php echo e($s->disposisi); ?></p><?php endif; ?>
            <div class="row" style="margin-top:8px;gap:6px">
                <?php if($arah === 'keluar'): ?><a class="btn btn-sm" target="_blank" href="<?php echo e(route('tu.surat.cetak', $s)); ?>">🖨 Cetak</a><?php endif; ?>
                <?php if($s->berkas_path): ?><a class="btn btn-sm" target="_blank" href="<?php echo e(route('tu.surat.berkas', $s)); ?>">📎 Lihat scan</a><?php endif; ?>
            </div>
            <?php if($canWrite): ?>
            <details class="more"><summary>Ubah / hapus</summary>
                <form method="post" action="<?php echo e(route('tu.surat.ubah', $s)); ?>" enctype="multipart/form-data"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <?php echo $__env->make('tu._surat-fields', ['rec' => $s, 'px' => 'e'.$s->id.'-'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <button class="btn btn-sm btn-pri" data-busy="Menyimpan…">Simpan perubahan</button>
                </form>
                <form method="post" action="<?php echo e(route('tu.surat.hapus', $s)); ?>" data-confirm="Hapus surat ini dari register?" style="margin-top:8px"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-sm btn-danger">Hapus surat</button></form>
            </details>
            <?php endif; ?>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty">Belum ada surat <?php echo e($arah); ?> tahun <?php echo e($tahun); ?>.</div>
    <?php endif; ?>
    <?php echo $__env->make('partials.pager', ['p' => $list], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => $arah === 'masuk' ? 'Surat Masuk' : 'Surat Keluar'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/tu/surat.blade.php ENDPATH**/ ?>