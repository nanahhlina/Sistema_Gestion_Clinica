<?php $__env->startSection('title', 'No pudimos completar la solicitud'); ?>
<?php $__env->startSection('content'); ?>
    <section class="error-page"><p class="error-code">500</p><h1>Ocurrió un problema</h1><p class="text-muted">No pudimos completar esta solicitud. Inténtalo nuevamente dentro de unos momentos.</p><div class="button-row"><a class="button button-primary" href="<?php echo e(route('home')); ?>">Volver al inicio</a></div></section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/errors/500.blade.php ENDPATH**/ ?>