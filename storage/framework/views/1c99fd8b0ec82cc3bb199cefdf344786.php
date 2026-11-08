<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['value']));

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

foreach (array_filter((['value']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $status = mb_strtolower((string) $value);
    $tone = match ($status) {
        'confirmada', 'confirmado', 'atendida', 'atendido', 'completada', 'completado', 'publicada', 'publicado', 'finalizada', 'finalizado', 'activo', 'activa', 'aprobada', 'enviado', 'enviada' => 'success',
        'cancelada', 'cancelado', 'inasistencia', 'inactivo', 'inactiva', 'rechazada', 'fallido', 'fallida' => 'danger',
        'pendiente', 'solicitada', 'solicitado', 'programada', 'programado' => 'warning',
        default => 'info',
    };
?>
<span <?php echo e($attributes->class(['badge', 'badge-'.$tone])); ?>><span class="status-dot" aria-hidden="true"></span><?php echo e($value === null || $value === '' ? 'Sin estado' : ucfirst(str_replace('_', ' ', (string) $value))); ?></span>
<?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/components/status.blade.php ENDPATH**/ ?>