<?php $__env->startSection('body-class', 'app-page workflow-page'); ?>
<?php $__env->startSection('title', 'Recordatorio #'.$row->id); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal8026f1991abb42645b4d7cc7ace47942 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8026f1991abb42645b4d7cc7ace47942 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-heading','data' => ['title' => 'Recordatorio #'.$row->id,'back' => route('reminders.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Recordatorio #'.$row->id),'back' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('reminders.index'))]); ?>
     <?php $__env->slot('actions', null, []); ?> 
        <?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['value' => $row->estado]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row->estado)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
     <?php $__env->endSlot(); ?>
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


<?php if($citaActual && $citaActual->estado === 'Cancelada' && in_array($row->estado, ['Programado', 'Fallido'])): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'alert']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
        <div style="flex: 1;">
            <strong>¡Atención! La cita médica asociada fue cancelada.</strong>
            <p style="margin: 4px 0 8px;">Motivo de cancelación de la cita: <em><?php echo e($citaActual->motivo_cancelacion ?: 'Sin motivo registrado'); ?></em>.</p>
            <form method="POST" action="<?php echo e(route('reminders.sync', $row->id)); ?>" style="display:inline;">
                <?php echo csrf_field(); ?>
                <button type="submit" class="button button-small button-danger">
                    <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
<?php endif; ?> Cancelar este recordatorio automáticamente
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>


<?php if($row->estado === 'Fallido'): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'alert']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
        <div style="flex: 1;">
            <strong>Fallo en el envío del recordatorio (Flujo Secundario - Error de envío)</strong>
            <p style="margin: 4px 0 8px;">Detalle del error registrado: <code><?php echo e($row->error_envio); ?></code></p>
            <p style="margin: 0 0 10px;">Intentos acumulados: <?php echo e($row->intentos); ?>. Puedes corregir los datos de contacto o reintentar el envío ahora.</p>
            <div class="button-row">
                <form method="POST" action="<?php echo e(route('reminders.send', $row->id)); ?>" style="display:inline;">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="button button-small button-primary">
                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'arrow-right']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-right']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Reintentar envío ahora
                    </button>
                </form>
                <a class="button button-small" href="<?php echo e(route('reminders.edit', $row->id)); ?>">
                    <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'edit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'edit']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Modificar datos de contacto / medio
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
    
    <article class="table-panel" style="padding: 20px;">
        <h2 style="margin-top: 0; color: var(--blue-dark); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            Datos del recordatorio
        </h2>
        <dl style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; margin: 0;">
            <dt style="font-weight: 600; color: var(--muted);">Estado:</dt>
            <dd style="margin: 0;"><?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['value' => $row->estado]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row->estado)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?></dd>

            <dt style="font-weight: 600; color: var(--muted);">Destinatario:</dt>
            <dd style="margin: 0;"><span class="badge badge-info"><?php echo e($row->destinatario); ?></span></dd>

            <dt style="font-weight: 600; color: var(--muted);">Medio de envío:</dt>
            <dd style="margin: 0;"><strong><?php echo e($row->medio); ?></strong></dd>

            <dt style="font-weight: 600; color: var(--muted);">Contacto destino:</dt>
            <dd style="margin: 0;"><code><?php echo e($row->contacto_destino); ?></code></dd>

            <?php if($row->contacto_doctor): ?>
                <dt style="font-weight: 600; color: var(--muted);">Contacto médico:</dt>
                <dd style="margin: 0;"><code><?php echo e($row->contacto_doctor); ?></code></dd>
            <?php endif; ?>

            <dt style="font-weight: 600; color: var(--muted);">Programado para:</dt>
            <dd style="margin: 0;"><?php echo e(\App\Modules\Shared\Clinic::date($row->fecha_programada)->format('d/m/Y H:i')); ?></dd>

            <dt style="font-weight: 600; color: var(--muted);">Fecha de envío:</dt>
            <dd style="margin: 0;"><?php echo e($row->fecha_envio ? \App\Modules\Shared\Clinic::date($row->fecha_envio)->format('d/m/Y H:i:s') : 'Pendiente'); ?></dd>

            <dt style="font-weight: 600; color: var(--muted);">Intentos:</dt>
            <dd style="margin: 0;"><?php echo e($row->intentos); ?></dd>

            <dt style="font-weight: 600; color: var(--muted);">Registrado por:</dt>
            <dd style="margin: 0;"><?php echo e($row->registrado_por_nombre); ?> (<?php echo e(\App\Modules\Shared\Clinic::date($row->creado_en)->format('d/m/Y H:i')); ?>)</dd>

            <?php if($row->motivo_cancelacion): ?>
                <dt style="font-weight: 600; color: var(--danger);">Cancelación:</dt>
                <dd style="margin: 0; color: var(--danger);"><?php echo e($row->motivo_cancelacion); ?></dd>
            <?php endif; ?>
        </dl>

        <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border);">
            <strong style="display: block; margin-bottom: 6px;">Mensaje a enviar:</strong>
            <blockquote style="margin: 0; padding: 12px 14px; background: #f8fafc; border-left: 4px solid var(--blue); border-radius: 4px; font-style: normal; line-height: 1.5;">
                <?php echo e($row->mensaje); ?>

            </blockquote>
        </div>
    </article>

    
    <article class="table-panel" style="padding: 20px;">
        <h2 style="margin-top: 0; color: var(--blue-dark); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            Cita médica vinculada
        </h2>
        <dl style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; margin: 0;">
            <dt style="font-weight: 600; color: var(--muted);">Cita:</dt>
            <dd style="margin: 0;"><a href="<?php echo e(route('appointments.show', $row->cita_id)); ?>"><strong><?php echo e($row->cita_codigo); ?></strong></a></dd>

            <dt style="font-weight: 600; color: var(--muted);">Estado de cita:</dt>
            <dd style="margin: 0;"><?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['value' => $row->cita_estado]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row->cita_estado)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?></dd>

            <dt style="font-weight: 600; color: var(--muted);">Fecha y hora:</dt>
            <dd style="margin: 0;"><strong><?php echo e(\App\Modules\Shared\Clinic::date($row->cita_inicio)->format('d/m/Y H:i')); ?></strong> – <?php echo e(\App\Modules\Shared\Clinic::date($row->cita_fin)->format('H:i')); ?></dd>

            <dt style="font-weight: 600; color: var(--muted);">Especialidad:</dt>
            <dd style="margin: 0;"><?php echo e($row->especialidad); ?></dd>

            <dt style="font-weight: 600; color: var(--muted);">Paciente:</dt>
            <dd style="margin: 0;">
                <strong><?php echo e($row->paciente_nombre); ?></strong> (CI: <?php echo e($row->paciente_ci); ?>)
                <br><small class="text-muted">Tel: <?php echo e($row->paciente_telefono); ?> | Correo: <?php echo e($row->paciente_correo ?: '—'); ?></small>
            </dd>

            <dt style="font-weight: 600; color: var(--muted);">Médico:</dt>
            <dd style="margin: 0;">
                <strong>Dr(a). <?php echo e($row->medico_nombre); ?></strong>
                <br><small class="text-muted">Tel: <?php echo e($row->medico_telefono ?: '—'); ?> | Correo: <?php echo e($row->medico_correo ?: '—'); ?></small>
            </dd>
        </dl>

        <?php if(!auth()->user()->isPatient() && !auth()->user()->isDoctor() && in_array($row->estado, ['Programado', 'Fallido'])): ?>
            <div style="margin-top: 24px; padding-top: 14px; border-top: 1px solid var(--border);">
                <strong style="display: block; margin-bottom: 10px;">Acciones de gestión:</strong>
                <div class="button-row">
                    <form method="POST" action="<?php echo e(route('reminders.send', $row->id)); ?>" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="button button-primary">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'arrow-right']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-right']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Enviar ahora
                        </button>
                    </form>
                    <a class="button" href="<?php echo e(route('reminders.edit', $row->id)); ?>">
                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'edit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'edit']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Modificar
                    </a>
                    <a class="button button-danger" href="<?php echo e(route('reminders.cancel.form', $row->id)); ?>">
                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
<?php endif; ?> Cancelar recordatorio
                    </a>
                </div>

                
                <div style="margin-top: 14px; padding-top: 10px; border-top: 1px dashed var(--border);">
                    <form method="POST" action="<?php echo e(route('reminders.send', $row->id)); ?>" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="simular_fallo" value="1">
                        <button type="submit" class="button button-small" title="Prueba el registro del fallo y cambio de estado a Fallido" style="color: #a62d42;">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'alert']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Simular fallo de envío (Flujo Secundario)
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </article>
</div>


<section class="table-panel" style="padding: 20px;">
    <h2>Historial de eventos y envíos</h2>
    <p class="text-muted">Trazabilidad completa de registros, modificaciones, intentos de envío y cancelaciones (Paso 10).</p>

    <?php if(count($history)): ?>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Acción</th>
                        <th>Estado anterior</th>
                        <th>Estado nuevo</th>
                        <th>Detalle</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="cell-date"><?php echo e(\App\Modules\Shared\Clinic::date($item->fecha)->format('d/m/Y H:i:s')); ?></td>
                            <td><strong><?php echo e($item->accion); ?></strong></td>
                            <td><?php echo e($item->estado_anterior ?: '—'); ?></td>
                            <td><?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['value' => $item->estado_nuevo]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->estado_nuevo)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?></td>
                            <td><?php echo e($item->detalle); ?></td>
                            <td><?php echo e($item->usuario_nombre ?: 'Sistema automático'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">Sin eventos registrados aún.</p>
    <?php endif; ?>
</section>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/recordatorios/show.blade.php ENDPATH**/ ?>