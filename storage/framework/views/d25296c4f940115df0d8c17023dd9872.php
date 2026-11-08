<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="login-url" content="<?php echo e(route('login')); ?>">
    <meta name="theme-color" content="#427cbe">
    <title><?php echo $__env->yieldContent('title', 'Inicio'); ?> · Clínica Vida Salud</title>
    <link rel="icon" href="<?php echo e(asset('assets/favicon.svg')); ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?php echo e(asset('assets/app.css')); ?>?v=<?php echo e(substr(hash_file('sha256', public_path('assets/app.css')), 0, 16)); ?>">
    <script src="<?php echo e(asset('assets/app.js')); ?>?v=<?php echo e(substr(hash_file('sha256', public_path('assets/app.js')), 0, 16)); ?>" type="module"></script>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="<?php echo $__env->yieldContent('body-class', 'app-page'); ?>">
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <?php if (! (View::hasSection('hide-header'))): ?>
        <header class="site-header">
            <div class="header-inner">
                <a class="brand" href="<?php echo e(route('home')); ?>" aria-label="Clínica Vida Salud · Inicio"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'cross']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cross']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><span>Clínica Vida Salud</span></a>
                <?php if(auth()->guard()->check()): ?>
                    <div class="header-actions">
                        <span class="user-name"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'user']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> <?php echo e(auth()->user()->display_name); ?></span>
                        <a class="logout-button" href="<?php echo e(route('logout.confirm')); ?>"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'logout']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'logout']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><span>Cerrar sesión</span></a>
                    </div>
                <?php endif; ?>
            </div>
        </header>
    <?php endif; ?>
    <main id="contenido" class="<?php echo $__env->yieldContent('main-class', 'container main-content'); ?>" tabindex="-1">
        <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->yieldContent('content'); ?>
    </main>
    <?php if (! (View::hasSection('hide-footer'))): ?><footer class="site-footer">Clínica Vida Salud <span aria-hidden="true">·</span> Atención y bienestar en cada paso</footer><?php endif; ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/layouts/app.blade.php ENDPATH**/ ?>