<?php $__env->startSection('content'); ?>
<div class="head"><div><h1><?php echo e('Mutasi Siswa'); ?></h1><small><?php echo e('Siswa pindahan masuk dan siswa yang pindah/berhenti/keluar. Perubahan ditulis ke Pusat, lalu semua aplikasi ikut terbarui.'); ?></small></div></div>

<?php if($canWrite): ?>
<div class="grid g2" style="align-items:start">
    <details class="card" <?php if($errors->any() && old('nis')): ?> open <?php endif; ?>><summary style="font-weight:700">+ Siswa Masuk (pindahan / baru)</summary>
        <form method="post" action="<?php echo e(route('tu.mutasi.masuk')); ?>" style="margin-top:12px"><?php echo csrf_field(); ?>
            <div class="fields">
                <div><label for="m-nis">NIS</label><input id="m-nis" type="text" name="nis" value="<?php echo e(old('nis')); ?>" required></div>
                <div><label for="m-nama">Nama siswa</label><input id="m-nama" type="text" name="nama" value="<?php echo e(old('nama')); ?>" required></div>
                <div><label for="m-jk">JK</label><?php echo $__env->make('partials.select', ['name' => 'jk', 'options' => ['L' => 'Laki-laki', 'P' => 'Perempuan'], 'selected' => old('jk')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>
                <div><label for="m-kelas">Masuk ke kelas</label><?php echo $__env->make('partials.select', ['name' => 'kelas_id', 'options' => $kelas->pluck('nama', 'id')->all(), 'selected' => old('kelas_id')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>
                <div><label for="m-tgl">Tanggal masuk</label><input id="m-tgl" type="date" name="tanggal" value="<?php echo e(old('tanggal', $today)); ?>" max="<?php echo e($today); ?>" required></div>
                <div><label for="m-asal">Sekolah asal</label><input id="m-asal" type="text" name="sekolah" value="<?php echo e(old('sekolah')); ?>" placeholder="isi - bila siswa baru" required></div>
                <div><label for="m-no">No. surat pindah <small>(opsional)</small></label><input id="m-no" type="text" name="nomor_surat" value="<?php echo e(old('nomor_surat')); ?>"></div>
                <div><label for="m-al">Alasan <small>(opsional)</small></label><input id="m-al" type="text" name="alasan" value="<?php echo e(old('alasan')); ?>"></div>
                <div><label for="m-ortu">Nama orang tua <small>(opsional)</small></label><input id="m-ortu" type="text" name="nama_ortu" value="<?php echo e(old('nama_ortu')); ?>"></div>
                <div><label for="m-nisn">NISN <small>(opsional)</small></label><input id="m-nisn" type="text" name="nisn" value="<?php echo e(old('nisn')); ?>" inputmode="numeric"></div>
                <div><label for="m-hp">No. HP/WA orang tua <small>(opsional)</small></label><input id="m-hp" type="tel" name="no_hp_ortu" value="<?php echo e(old('no_hp_ortu')); ?>" inputmode="tel" placeholder="08xxxxxxxxxx"></div>
            </div>
            <button class="btn btn-pri" style="margin-top:12px" data-busy="Menyimpan…">Simpan &amp; Tambahkan di Pusat</button>
        </form>
    </details>
    <details class="card" <?php if($errors->any() && old('siswa_id')): ?> open <?php endif; ?>><summary style="font-weight:700">− Siswa Keluar (pindah / berhenti)</summary>
        <form method="post" action="<?php echo e(route('tu.mutasi.keluar')); ?>" style="margin-top:12px" data-confirm="Status siswa diubah di Pusat; siswa tidak lagi muncul di Absensi, BK, dan aplikasi lain. Lanjutkan?"><?php echo csrf_field(); ?>
            <div class="fields">
                <div><label for="k-kelas">Kelas</label><select id="k-kelas" data-pick-class="#k-siswa"><option value="">— pilih kelas —</option><?php $__currentLoopData = $kelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(isset($pickSiswa[$c->id])): ?><option value="<?php echo e($c->id); ?>"><?php echo e($c->nama); ?></option><?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div style="grid-column:span 2"><label for="k-siswa">Nama siswa</label><select id="k-siswa" name="siswa_id" required><option value="">— pilih siswa —</option>
                    <?php $__currentLoopData = $kelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(isset($pickSiswa[$c->id])): ?><optgroup label="<?php echo e($c->nama); ?>" data-class="<?php echo e($c->id); ?>"><?php $__currentLoopData = $pickSiswa[$c->id]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s->id); ?>" <?php if((int) old('siswa_id') === $s->id): echo 'selected'; endif; ?>><?php echo e($s->nama); ?> (<?php echo e($s->nis); ?>)</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></optgroup><?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div><label for="k-st">Jenis</label><?php echo $__env->make('partials.select', ['name' => 'status', 'options' => array_map(fn ($a) => $a[0], \App\Services\TuService::ALASAN_KELUAR), 'selected' => old('status', 'pindah')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>
                <div><label for="k-tgl">Tanggal keluar</label><input id="k-tgl" type="date" name="tanggal" value="<?php echo e(old('tanggal', $today)); ?>" max="<?php echo e($today); ?>" required></div>
                <div><label for="k-sek">Sekolah tujuan <small>(bila pindah)</small></label><input id="k-sek" type="text" name="sekolah" value="<?php echo e(old('sekolah')); ?>"></div>
                <div><label for="k-al">Alasan <small>(opsional)</small></label><input id="k-al" type="text" name="alasan" value="<?php echo e(old('alasan')); ?>"></div>
            </div>
            <label class="chk" style="margin-top:8px"><input type="checkbox" name="buat_surat" value="1" checked> Buatkan Surat Keterangan Pindah (nomor otomatis, bila jenis "pindah")</label>
            <button class="btn btn-pri" style="margin-top:8px" data-busy="Menyimpan…">Simpan Mutasi Keluar</button>
        </form>
    </details>
</div>
<?php endif; ?>

<form method="get" action="<?php echo e(route('tu.mutasi')); ?>" class="card filter-card">
    <div class="fields">
        <div><label for="q">Cari</label><input id="q" type="search" name="q" value="<?php echo e($q); ?>" placeholder="Nama / NIS"></div>
        <div><label for="fj">Jenis</label><select id="fj" name="jenis" data-auto><option value="">Semua</option><option value="masuk" <?php if($jenis === 'masuk'): echo 'selected'; endif; ?>>Masuk</option><option value="keluar" <?php if($jenis === 'keluar'): echo 'selected'; endif; ?>>Keluar</option></select></div>
        <div style="align-self:end"><button class="btn">Cari</button></div>
    </div>
</form>

<div class="card card-tight" id="daftar-mutasi" data-swap>
    <?php if($list->isEmpty()): ?><div class="empty">Belum ada catatan mutasi.</div><?php else: ?>
    <div class="scroll"><table class="tbl tbl-cards"><thead><tr><th>Tanggal</th><th>Siswa</th><th>Jenis</th><th>Kelas</th><th>Sekolah</th><th>Alasan / No. surat</th></tr></thead><tbody>
    <?php $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr><td class="mono" data-label="Tanggal"><?php echo e(\App\Support\Dates::human($m->tanggal)); ?></td>
            <td class="card-title"><b><?php echo e($m->siswa->nama ?? '(dihapus)'); ?></b> <small class="mut"><?php echo e($m->siswa->nis ?? ''); ?></small></td>
            <td data-label="Jenis"><?php if($m->jenis === 'masuk'): ?><span class="badge b-ok">Masuk</span><?php else: ?><span class="badge b-warn">Keluar · <?php echo e(\App\Services\TuService::alasanLabel($m->status_baru)); ?></span><?php endif; ?></td>
            <td data-label="Kelas"><?php echo e($m->kelas->nama ?? '-'); ?></td>
            <td data-label="<?php echo e($m->jenis === 'masuk' ? 'Sekolah asal' : 'Tujuan'); ?>"><?php echo e($m->sekolah ?: '-'); ?></td>
            <td data-label="Alasan"><?php echo e($m->alasan ?: '-'); ?><?php if($m->nomor_surat): ?><br><small class="mono mut"><?php echo e($m->nomor_surat); ?></small><?php endif; ?></td></tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody></table></div><?php endif; ?>
    <?php echo $__env->make('partials.pager', ['p' => $list], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Mutasi Siswa'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/tu/mutasi.blade.php ENDPATH**/ ?>