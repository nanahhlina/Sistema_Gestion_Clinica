<?php $__env->startSection('body-class','app-page workflow-page'); ?>
<?php $__env->startSection('title', $own ? 'Reservar cita médica' : 'Agendar cita médica'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal8026f1991abb42645b4d7cc7ace47942 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8026f1991abb42645b4d7cc7ace47942 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-heading','data' => ['title' => $own ? 'Reservar cita médica' : 'Agendar cita médica','back' => route('home')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($own ? 'Reservar cita médica' : 'Agendar cita médica'),'back' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('home'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8026f1991abb42645b4d7cc7ace47942)): ?>
<?php $attributes = $__attributesOriginal8026f1991abb42645b4d7cc7ace47942; ?>
<?php unset($__attributesOriginal8026f1991abb42645b4d7cc7ace47942); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8026f1991abb42645b4d7cc7ace47942)): ?>
<?php $component = $__componentOriginal8026f1991abb42645b4d7cc7ace47942; ?>
<?php unset($__componentOriginal8026f1991abb42645b4d7cc7ace47942); ?>
<?php endif; ?>
<section data-availability="<?php echo e(route($own?'reservations.availability':'appointments.availability')); ?>">
<form method="get" id="appointment-filters" class="filters" data-appointment-filter>
<?php if (! ($own)): ?>
<label>Buscar paciente <input name="q" maxlength="100" value="<?php echo e($filters['q'] ?? ''); ?>"></label>
<?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'paciente_id','label' => 'Paciente activo','type' => 'select','options' => [''=>'Selecciona un paciente']+$patients,'value' => $patient]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'paciente_id','label' => 'Paciente activo','type' => 'select','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([''=>'Selecciona un paciente']+$patients),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patient)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php endif; ?>
<?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'especialidad_id','label' => 'Especialidad','type' => 'select','options' => [''=>'Selecciona una especialidad']+$specialties,'value' => $specialty]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'especialidad_id','label' => 'Especialidad','type' => 'select','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([''=>'Selecciona una especialidad']+$specialties),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($specialty)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'fecha','label' => 'Fecha','type' => 'date','value' => $filters['fecha'] ?? null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'fecha','label' => 'Fecha','type' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['fecha'] ?? null)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<button class="button button-primary">Consultar disponibilidad</button>
</form>
<?php if($specialty && !$doctors): ?><p role="status">No hay médicos disponibles para esta especialidad.</p><?php endif; ?>
<?php if($specialty && $patient && !empty($filters['fecha']) && $doctors): ?>
<div class="form-panel">
<?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'medico_id','id' => 'medico-disponibilidad','form' => 'appointment-filters','label' => 'Médico','type' => 'select','options' => [''=>'Selecciona un médico']+$doctors,'value' => $doctor,'hint' => 'Selecciona un médico y consulta sus turnos para la fecha indicada.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'medico_id','id' => 'medico-disponibilidad','form' => 'appointment-filters','label' => 'Médico','type' => 'select','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([''=>'Selecciona un médico']+$doctors),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($doctor),'hint' => 'Selecciona un médico y consulta sus turnos para la fecha indicada.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<button type="submit" form="appointment-filters" class="button button-primary">Consultar turnos del médico</button>
</div>
<?php endif; ?>
<?php if($slots): ?>
<form method="post" action="<?php echo e($action); ?>" class="form-panel"><?php echo csrf_field(); ?>
<input type="hidden" name="clave" value="<?php echo e($key); ?>"><input type="hidden" name="firma" value="<?php echo e(\App\Modules\Shared\Services\OperacionWeb::signature(auth()->user(),$operation,$key)); ?>">
<?php if (! ($own)): ?><input type="hidden" name="paciente_id" value="<?php echo e($patient); ?>"><?php endif; ?>
<input type="hidden" name="especialidad_id" value="<?php echo e($specialty); ?>"><input type="hidden" name="medico_id" value="<?php echo e($doctor); ?>">
<?php if(!$own && auth()->user()->hasPermission('pagos.registrar')): ?>
<?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'precio_consulta','label' => 'Precio de consulta (BOB, opcional)','inputmode' => 'decimal','maxlength' => '13','hint' => 'Vacío: sin definir. Cero: sin cargo.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'precio_consulta','label' => 'Precio de consulta (BOB, opcional)','inputmode' => 'decimal','maxlength' => '13','hint' => 'Vacío: sin definir. Cero: sin cargo.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php endif; ?>
<?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'inicio','label' => 'Turno disponible (inicio y fin)','type' => 'select','options' => $slots,'required' => true,'hint' => 'Selecciona un turno para revisar y confirmar la cita.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'inicio','label' => 'Turno disponible (inicio y fin)','type' => 'select','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($slots),'required' => true,'hint' => 'Selecciona un turno para revisar y confirmar la cita.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $attributes = $__attributesOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__attributesOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalae4c123bc9806121d87d234de2f27a3b)): ?>
<?php $component = $__componentOriginalae4c123bc9806121d87d234de2f27a3b; ?>
<?php unset($__componentOriginalae4c123bc9806121d87d234de2f27a3b); ?>
<?php endif; ?>
<div class="field"><label for="motivo">Motivo de consulta (opcional)</label><textarea id="motivo" name="motivo_consulta" maxlength="2000"></textarea></div>
<p role="status" data-availability-status></p><button type="button" class="button" data-availability-retry hidden>Reintentar consulta</button>
<button name="paso" value="revisar" class="button button-primary" data-book-appointment>Revisar cita</button>
</form>
<?php elseif($doctor && $patient && !empty($filters['fecha'])): ?><p role="status">No hay horarios disponibles para esta fecha. Selecciona otra fecha o médico.</p>
<?php elseif($specialty && $patient && !empty($filters['fecha']) && $doctors): ?><p>Selecciona el médico en la sección inferior y pulsa Consultar turnos del médico.</p>
<?php else: ?><p><?php echo e($own ? 'Selecciona especialidad y fecha.' : 'Selecciona paciente, especialidad y fecha.'); ?> Pulsa Consultar disponibilidad para elegir el médico en la sección inferior.</p><?php endif; ?>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/appointments/create.blade.php ENDPATH**/ ?>