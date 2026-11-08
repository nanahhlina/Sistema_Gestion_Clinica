<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['items' => []]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['items' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php if(count($items)): ?><dl class="stats-grid" aria-label="Resumen"><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $number): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div class="stat-card"><dt><?php echo e($label); ?></dt><dd><?php echo e(is_numeric($number) ? number_format((float) $number, 0, ',', '.') : $number); ?></dd></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></dl><?php endif; ?>
<?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/components/stats.blade.php ENDPATH**/ ?>