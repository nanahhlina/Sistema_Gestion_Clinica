<?php $__env->startSection('title', 'Módulos'); ?>
<?php $__env->startSection('content'); ?>
    <div class="home-heading"><h1>Módulos</h1><p>Selecciona una opción para comenzar.</p></div>
    <div class="module-grid">
        <?php $__empty_1 = true; $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a class="module-card" href="<?php echo e(data_get($module, 'url')); ?>"><span class="module-icon"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => data_get($module, 'icon', 'grid')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(data_get($module, 'icon', 'grid'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></span><h2><?php echo e(data_get($module, 'title')); ?></h2><p><?php echo e(data_get($module, 'description')); ?></p></a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['class' => 'grid-full','title' => 'Tu acceso está listo','message' => 'Aún no tienes módulos asignados. Comunícate con la administración de la clínica para revisar tus permisos.','icon' => 'shield']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'grid-full','title' => 'Tu acceso está listo','message' => 'Aún no tienes módulos asignados. Comunícate con la administración de la clínica para revisar tus permisos.','icon' => 'shield']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $attributes = $__attributesOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__attributesOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $component = $__componentOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__componentOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if(count($stats ?? [])): ?><section class="home-summary" aria-label="Actividad de la clínica"><h2 class="section-title">Resumen de actividad</h2><?php if (isset($component)) { $__componentOriginalda50c8a736c70542f601e56d53889e42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalda50c8a736c70542f601e56d53889e42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stats','data' => ['items' => $stats]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stats'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalda50c8a736c70542f601e56d53889e42)): ?>
<?php $attributes = $__attributesOriginalda50c8a736c70542f601e56d53889e42; ?>
<?php unset($__attributesOriginalda50c8a736c70542f601e56d53889e42); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalda50c8a736c70542f601e56d53889e42)): ?>
<?php $component = $__componentOriginalda50c8a736c70542f601e56d53889e42; ?>
<?php unset($__componentOriginalda50c8a736c70542f601e56d53889e42); ?>
<?php endif; ?></section><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/home.blade.php ENDPATH**/ ?>