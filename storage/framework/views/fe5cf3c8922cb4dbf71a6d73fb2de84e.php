<?php $__env->startSection('body-class', 'app-page workflow-page'); ?>
<?php $__env->startSection('title', 'Registrar recordatorio'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal8026f1991abb42645b4d7cc7ace47942 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8026f1991abb42645b4d7cc7ace47942 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-heading','data' => ['title' => 'Registrar recordatorio','description' => 'Configura un recordatorio de cita médica para el paciente y/o el médico.','back' => route('reminders.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Registrar recordatorio','description' => 'Configura un recordatorio de cita médica para el paciente y/o el médico.','back' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('reminders.index'))]); ?>
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

<?php if($errors->any()): ?>
    <div class="alert alert-danger" role="alert">
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
        <div>
            <strong>Revisa los siguientes campos:</strong>
            <ul>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
<?php endif; ?>


<section class="table-panel" style="padding: 20px; margin-bottom: 24px;">
    <h2>1. Seleccionar paciente</h2>
    <form method="GET" action="<?php echo e(route('reminders.create')); ?>" class="filters" style="margin-bottom: 16px;">
        <div style="display: flex; gap: 10px; align-items: flex-end;">
            <div style="flex: 1;">
                <label for="q" style="font-weight: 600; display: block; margin-bottom: 6px;">Buscar paciente por nombre o CI:</label>
                <input type="text" id="q" name="q" value="<?php echo e($search); ?>" placeholder="Ej: Ana o TEST-001" style="width: 100%; min-height: 42px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
            </div>
            <button type="submit" class="button button-primary"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'search']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Buscar</button>
            <?php if($search): ?>
                <a class="button" href="<?php echo e(route('reminders.create')); ?>">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <form method="GET" action="<?php echo e(route('reminders.create')); ?>">
        <?php if($search): ?><input type="hidden" name="q" value="<?php echo e($search); ?>"><?php endif; ?>
        <div style="display: flex; gap: 10px; align-items: flex-end;">
            <div style="flex: 1;">
                <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'paciente_id','label' => 'Pacientes encontrados','type' => 'select','options' => ['' => 'Selecciona un paciente...'] + $patients,'value' => $patientId,'onchange' => 'this.form.submit()','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'paciente_id','label' => 'Pacientes encontrados','type' => 'select','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['' => 'Selecciona un paciente...'] + $patients),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patientId),'onchange' => 'this.form.submit()','required' => true]); ?>
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
            </div>
            <noscript>
                <button type="submit" class="button">Elegir paciente</button>
            </noscript>
        </div>
    </form>
</section>


<?php if($selectedPatient): ?>
    <section class="table-panel" style="padding: 20px; margin-bottom: 24px;">
        <h2>2. Seleccionar cita médica asociada</h2>
        <p><strong>Paciente seleccionado:</strong> <?php echo e($selectedPatient->nombres); ?> <?php echo e($selectedPatient->apellidos); ?> (CI: <?php echo e($selectedPatient->ci ?: 'Sin CI'); ?>) · Tel: <?php echo e($selectedPatient->telefono); ?> · Correo: <?php echo e($selectedPatient->correo ?: 'Sin correo registrado'); ?></p>

        <?php if(empty($patientAppointments)): ?>
            <div class="alert alert-warning">
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
                <div>
                    <strong>El paciente no tiene citas activas programadas.</strong>
                    <p style="margin: 4px 0 0;">Solo se pueden programar recordatorios para citas en estado <em>Programada</em> o <em>Confirmada</em>.</p>
                </div>
            </div>
            <a class="button button-primary" href="<?php echo e(route('appointments.create', ['paciente_id' => $selectedPatient->id])); ?>">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'plus']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'plus']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Agendar nueva cita médica
            </a>
        <?php else: ?>
            <form method="GET" action="<?php echo e(route('reminders.create')); ?>" style="margin-top: 14px;">
                <input type="hidden" name="paciente_id" value="<?php echo e($selectedPatient->id); ?>">
                <?php if($search): ?><input type="hidden" name="q" value="<?php echo e($search); ?>"><?php endif; ?>
                <div style="display: flex; gap: 10px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label for="select-cita" style="font-weight: 600; display: block; margin-bottom: 6px;">Citas activas del paciente:</label>
                        <select id="select-cita" name="cita_id" onchange="this.form.submit()" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                            <?php $__currentLoopData = $patientAppointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $app): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($app->id); ?>" <?php echo e(($selectedCita && $selectedCita->id == $app->id) ? 'selected' : ''); ?>>
                                    <?php echo e($app->codigo); ?> · <?php echo e(\App\Modules\Shared\Clinic::date($app->inicio)->format('d/m/Y H:i')); ?> · <?php echo e($app->especialidad); ?> (Dr. <?php echo e($app->medico_nombre); ?>) [<?php echo e($app->estado); ?>]
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <noscript><button type="submit" class="button">Elegir cita</button></noscript>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>


<?php if($selectedCita): ?>
    <section class="table-panel" style="padding: 20px; margin-bottom: 24px; background: #fafcfe; border-color: #cbdff6;">
        <h2 style="color: var(--blue-dark); margin-bottom: 12px;">3. Datos de la cita médica</h2>
        <dl style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin: 0;">
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Código de cita</dt>
                <dd style="font-size: 1.1rem; font-weight: bold; margin: 4px 0 0;"><a href="<?php echo e(route('appointments.show', $selectedCita->id)); ?>"><?php echo e($selectedCita->codigo); ?></a></dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Fecha y horario</dt>
                <dd style="font-size: 1.05rem; font-weight: 600; margin: 4px 0 0;"><?php echo e(\App\Modules\Shared\Clinic::date($selectedCita->inicio)->format('d/m/Y H:i')); ?> – <?php echo e(\App\Modules\Shared\Clinic::date($selectedCita->fin)->format('H:i')); ?></dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Médico asignado</dt>
                <dd style="font-size: 1.05rem; font-weight: 600; margin: 4px 0 0;"><?php echo e($selectedCita->medico_nombre); ?></dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Especialidad</dt>
                <dd style="font-size: 1.05rem; font-weight: 600; margin: 4px 0 0;"><?php echo e($selectedCita->especialidad); ?></dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Contacto del paciente</dt>
                <dd style="margin: 4px 0 0;">
                    Tel: <strong><?php echo e($selectedCita->paciente_telefono ?: 'No registrado'); ?></strong><br>
                    Correo: <strong><?php echo e($selectedCita->paciente_correo ?: 'No registrado'); ?></strong>
                </dd>
            </div>
            <div>
                <dt style="font-size: .8rem; color: var(--muted); text-transform: uppercase;">Contacto del médico</dt>
                <dd style="margin: 4px 0 0;">
                    Tel: <strong><?php echo e($selectedCita->medico_telefono ?: 'No registrado'); ?></strong><br>
                    Correo: <strong><?php echo e($selectedCita->medico_correo ?: 'No registrado'); ?></strong>
                </dd>
            </div>
        </dl>
    </section>

    
    <form method="POST" action="<?php echo e(route('reminders.store')); ?>" class="form-panel" style="padding: 24px; border: 1px solid var(--border); border-radius: 12px; background: #fff;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="cita_id" value="<?php echo e($selectedCita->id); ?>">

        <h2>4. Configuración del recordatorio</h2>
        <p class="text-muted">Establece el destinatario, el medio y el momento en que se enviará la notificación.</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin: 18px 0;">
            <div>
                <label for="destinatario" style="font-weight: 600; display: block; margin-bottom: 6px;">Destinatario del recordatorio:</label>
                <select id="destinatario" name="destinatario" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                    <option value="Paciente" <?php echo e(old('destinatario') === 'Paciente' ? 'selected' : ''); ?>>Paciente (<?php echo e($selectedCita->paciente_nombre); ?>)</option>
                    <option value="Medico" <?php echo e(old('destinatario') === 'Medico' ? 'selected' : ''); ?>>Médico (<?php echo e($selectedCita->medico_nombre); ?>)</option>
                    <option value="Ambos" <?php echo e(old('destinatario') === 'Ambos' ? 'selected' : ''); ?>>Ambos (Paciente y Médico)</option>
                </select>
            </div>

            <div>
                <label for="medio" style="font-weight: 600; display: block; margin-bottom: 6px;">Medio de envío:</label>
                <select id="medio" name="medio" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                    <option value="Correo" <?php echo e(old('medio', 'Correo') === 'Correo' ? 'selected' : ''); ?>>Correo electrónico (Email)</option>
                    <option value="SMS" <?php echo e(old('medio') === 'SMS' ? 'selected' : ''); ?>>Mensaje de texto SMS</option>
                    <option value="WhatsApp" <?php echo e(old('medio') === 'WhatsApp' ? 'selected' : ''); ?>>WhatsApp</option>
                    <option value="Sistema" <?php echo e(old('medio') === 'Sistema' ? 'selected' : ''); ?>>Notificación interna en Portal</option>
                </select>
                <small class="table-note" style="display:block; margin-top: 4px;">El sistema validará que el destinatario cuente con el medio de contacto registrado.</small>
            </div>

            <div>
                <label for="momento_tipo" style="font-weight: 600; display: block; margin-bottom: 6px;">Momento de envío:</label>
                <select id="momento_tipo" name="momento_tipo" onchange="toggleFechaPersonalizada(this.value)" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
                    <option value="inmediato" <?php echo e(old('momento_tipo', 'inmediato') === 'inmediato' ? 'selected' : ''); ?>>Enviar de inmediato</option>
                    <option value="24h_antes" <?php echo e(old('momento_tipo') === '24h_antes' ? 'selected' : ''); ?>>24 horas antes de la cita</option>
                    <option value="2h_antes" <?php echo e(old('momento_tipo') === '2h_antes' ? 'selected' : ''); ?>>2 horas antes de la cita</option>
                    <option value="1d_8am" <?php echo e(old('momento_tipo') === '1d_8am' ? 'selected' : ''); ?>>1 día antes a las 08:00 AM</option>
                    <option value="personalizado" <?php echo e(old('momento_tipo') === 'personalizado' ? 'selected' : ''); ?>>Fecha y hora personalizada</option>
                </select>
            </div>

            <div id="wrapper-fecha-personalizada" style="<?php echo e(old('momento_tipo') === 'personalizado' ? '' : 'display: none;'); ?>">
                <label for="fecha_personalizada" style="font-weight: 600; display: block; margin-bottom: 6px;">Fecha y hora de envío personalizada:</label>
                <input type="datetime-local" id="fecha_personalizada" name="fecha_personalizada" value="<?php echo e(old('fecha_personalizada')); ?>" style="width: 100%; min-height: 44px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 7px;">
            </div>
        </div>

        <div style="margin: 18px 0;">
            <label for="mensaje" style="font-weight: 600; display: block; margin-bottom: 6px;">Mensaje del recordatorio:</label>
            <textarea id="mensaje" name="mensaje" rows="4" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 7px; font-family: inherit;"><?php echo e(old('mensaje', $defaultMessage)); ?></textarea>
            <small class="text-muted">Puedes personalizar este texto antes de guardar.</small>
        </div>

        
        <div style="background: #fff9e6; border: 1px solid #f2e2b0; border-radius: 8px; padding: 12px 16px; margin: 18px 0;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: .9rem; cursor: pointer;">
                <input type="checkbox" name="simular_fallo" value="1" <?php echo e(old('simular_fallo') ? 'checked' : ''); ?>>
                <span><strong>Simular fallo de envío</strong> (permite verificar el flujo secundario de error de entrega y registro del fallo).</span>
            </label>
        </div>

        <div class="heading-actions" style="margin-top: 24px;">
            <button type="submit" class="button button-primary">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'check']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Registrar y programar recordatorio
            </button>
            <a class="button" href="<?php echo e(route('reminders.index')); ?>">Cancelar</a>
        </div>
    </form>
<?php endif; ?>

<script>
function toggleFechaPersonalizada(value) {
    var el = document.getElementById('wrapper-fecha-personalizada');
    if (el) {
        el.style.display = (value === 'personalizado') ? 'block' : 'none';
    }
}
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/recordatorios/create.blade.php ENDPATH**/ ?>