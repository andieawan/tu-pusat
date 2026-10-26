<?php $__env->startSection('content'); ?>
<div class="card login">
    <img class="logo-login" src="<?php echo e(asset('icons/icon-192.png')); ?>" alt="" width="56" height="56">
    <h1>Tata Usaha</h1>
    <p class="mut"><?php echo e(config('tu.school_name')); ?><br>Masuk memakai akun Pusat Sekolah.</p>
    <?php if(\App\Services\PusatKlien::aktif()): ?><a class="btn btn-pri" style="width:100%;text-align:center;display:block" href="<?php echo e(route('sso.login')); ?>">Masuk lewat Pusat</a><?php endif; ?>
    <?php if(config('tu.login_lokal')): ?>
    <details style="margin-top:14px" <?php if($errors->any()): ?> open <?php endif; ?>><summary class="mut" style="cursor:pointer">Admin lokal (darurat)</summary>
    <form method="post" action="<?php echo e(route('login')); ?>" autocomplete="off" style="margin-top:10px">
        <?php echo csrf_field(); ?>
        <div class="field"><label for="u">Username</label><input id="u" type="text" name="username" value="<?php echo e(old('username')); ?>" autocapitalize="none" autocomplete="username" required></div>
        <div class="field"><label for="pw">Password</label><input id="pw" type="password" name="password" autocomplete="current-password" required></div>
        <button class="btn" style="width:100%" data-busy="Memeriksa…">Masuk</button>
    </form></details>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Masuk', 'bare' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/tu-pusat/resources/views/auth/login.blade.php ENDPATH**/ ?>