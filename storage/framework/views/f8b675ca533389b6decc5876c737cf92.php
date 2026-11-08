<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'required' => false, 'hint' => null, 'multiple' => false, 'scope' => null]));

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

foreach (array_filter((['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'required' => false, 'hint' => null, 'multiple' => false, 'scope' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $fieldId = $attributes->get('id', str_replace(['[', ']', '.'], '-', $name));
    $useOldInput = $scope === null || old('_section') === $scope;
    $fieldValue = $type === 'password' || $type === 'file' ? null : ($useOldInput ? old($name, $value) : $value);
    $hasError = $useOldInput && ($errors->has($name) || $errors->has($name.'.*'));
    $describedBy = trim(($hint ? $fieldId.'-hint ' : '').($hasError ? $fieldId.'-error' : ''));
?>
<div class="field <?php echo e($type === 'checkbox' ? 'field-checkbox' : ''); ?>">
    <?php if($type === 'checkbox'): ?>
        <input type="hidden" name="<?php echo e($name); ?>" value="0">
        <label class="checkbox-label" for="<?php echo e($fieldId); ?>"><input <?php echo e($attributes->except('id')); ?> id="<?php echo e($fieldId); ?>" type="checkbox" name="<?php echo e($name); ?>" value="1" <?php if((bool) $fieldValue): echo 'checked'; endif; ?> <?php if($required): echo 'required'; endif; ?> <?php if($hasError): ?> aria-invalid="true" <?php endif; ?> <?php if($describedBy): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>><span><?php echo e($label); ?></span></label>
    <?php else: ?>
        <label for="<?php echo e($fieldId); ?>"><?php echo e($label); ?> <?php if($required): ?><span class="required-mark" aria-hidden="true">*</span><span class="sr-only"> (obligatorio)</span><?php endif; ?></label>
        <?php if($type === 'textarea'): ?>
            <textarea <?php echo e($attributes->except('id')->class(['form-control'])); ?> id="<?php echo e($fieldId); ?>" name="<?php echo e($name); ?>" rows="4" <?php if($required): echo 'required'; endif; ?> <?php if($hasError): ?> aria-invalid="true" <?php endif; ?> <?php if($describedBy): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>><?php echo e($fieldValue); ?></textarea>
        <?php elseif($type === 'select' || $multiple): ?>
            <select <?php echo e($attributes->except('id')->class(['form-control'])); ?> id="<?php echo e($fieldId); ?>" name="<?php echo e($name); ?><?php echo e($multiple ? '[]' : ''); ?>" <?php if($multiple): ?> multiple <?php endif; ?> <?php if($required): echo 'required'; endif; ?> <?php if($hasError): ?> aria-invalid="true" <?php endif; ?> <?php if($describedBy): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>>
                <?php if(!$multiple && !array_key_exists('', $options)): ?><option value="">Selecciona una opción</option><?php endif; ?>
                <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionValue => $optionLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($optionValue); ?>" <?php if(in_array((string) $optionValue, array_map('strval', (array) $fieldValue), true)): echo 'selected'; endif; ?>><?php echo e($optionLabel); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        <?php elseif($type === 'password'): ?>
            <div class="password-control"><input <?php echo e($attributes->except('id')->class(['form-control'])); ?> id="<?php echo e($fieldId); ?>" type="password" name="<?php echo e($name); ?>" <?php if($required): echo 'required'; endif; ?> <?php if($hasError): ?> aria-invalid="true" <?php endif; ?> <?php if($describedBy): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>><button class="password-toggle" type="button" data-password-toggle="<?php echo e($fieldId); ?>" aria-label="Mostrar contraseña" aria-pressed="false" hidden><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'eye']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'eye']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></button></div>
        <?php else: ?>
            <input <?php echo e($attributes->except('id')->class(['form-control'])); ?> id="<?php echo e($fieldId); ?>" type="<?php echo e($type); ?>" name="<?php echo e($name); ?>" <?php if($type !== 'file'): ?> value="<?php echo e($fieldValue); ?>" <?php endif; ?> <?php if($required): echo 'required'; endif; ?> <?php if($hasError): ?> aria-invalid="true" <?php endif; ?> <?php if($describedBy): ?> aria-describedby="<?php echo e($describedBy); ?>" <?php endif; ?>>
        <?php endif; ?>
    <?php endif; ?>
    <?php if($hint): ?><p id="<?php echo e($fieldId); ?>-hint" class="field-hint"><?php echo e($hint); ?></p><?php endif; ?>
    <?php if($hasError): ?><p id="<?php echo e($fieldId); ?>-error" class="field-error"><?php echo e($errors->first($name) ?: $errors->first($name.'.*')); ?></p><?php endif; ?>
</div>
<?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/components/field.blade.php ENDPATH**/ ?>