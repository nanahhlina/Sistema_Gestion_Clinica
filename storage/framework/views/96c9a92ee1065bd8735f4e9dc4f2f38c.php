<?php $__env->startSection('title', $definition['title']); ?>
<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginal8026f1991abb42645b4d7cc7ace47942 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8026f1991abb42645b4d7cc7ace47942 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-heading','data' => ['title' => $definition['title'],'description' => $canWrite ? 'Consulta y administra los registros de la clínica.' : 'Consulta los registros disponibles de la clínica.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($definition['title']),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($canWrite ? 'Consulta y administra los registros de la clínica.' : 'Consulta los registros disponibles de la clínica.')]); ?>
        <?php if($canWrite): ?><a class="button button-primary" href="<?php echo e(route('catalog.create', ['module' => $module])); ?>"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
<?php endif; ?>Agregar <?php echo e(mb_strtolower($definition['singular'])); ?></a><?php endif; ?>
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
    <form method="GET" action="<?php echo e(route('catalog.index', ['module' => $module])); ?>" class="filters">
        <div class="filter-grid">
            <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'q','label' => 'Buscar','type' => 'search','value' => $filters['q'] ?? null,'placeholder' => $module === 'horarios' ? 'Nombre del médico u hora…' : 'Escribe para buscar…']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'q','label' => 'Buscar','type' => 'search','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['q'] ?? null),'placeholder' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module === 'horarios' ? 'Nombre del médico u hora…' : 'Escribe para buscar…')]); ?>
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
            <?php if($module === 'horarios'): ?>
                <?php if (isset($component)) { $__componentOriginalae4c123bc9806121d87d234de2f27a3b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalae4c123bc9806121d87d234de2f27a3b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'medico_id','label' => 'Médico','type' => 'select','options' => ['' => 'Todos los médicos'] + $doctors,'value' => $filters['medico_id'] ?? null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'medico_id','label' => 'Médico','type' => 'select','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['' => 'Todos los médicos'] + $doctors),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['medico_id'] ?? null)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.field','data' => ['name' => 'dia_semana','label' => 'Día de atención','type' => 'select','options' => ['' => 'Todos los días'] + config('clinic.catalogs.horarios.fields.dia_semana.options'),'value' => $filters['dia_semana'] ?? null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'dia_semana','label' => 'Día de atención','type' => 'select','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['' => 'Todos los días'] + config('clinic.catalogs.horarios.fields.dia_semana.options')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['dia_semana'] ?? null)]); ?>
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
            <div class="filter-actions"><button class="button button-primary" type="submit"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
<?php endif; ?>Buscar</button><a class="button" href="<?php echo e(route('catalog.index', ['module' => $module])); ?>">Limpiar</a></div>
        </div>
    </form>
    <div class="table-panel">
        <?php if($rows->count()): ?>
            <div class="table-scroll" role="region" aria-label="<?php echo e($definition['title']); ?>" tabindex="0"><table><caption class="sr-only">Listado de <?php echo e(mb_strtolower($definition['title'])); ?></caption><thead><tr><?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th scope="col"><?php echo e($label); ?></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> <?php if($hasActions): ?><th scope="col">Acciones</th><?php endif; ?></tr></thead><tbody>
                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <td><?php if(in_array($field, ['activo', 'active'], true)): ?><?php if (isset($component)) { $__componentOriginal51ed764111e345fc11534f121cfeb451 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ed764111e345fc11534f121cfeb451 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status','data' => ['value' => data_get($row, $field) ? 'Activo' : 'Inactivo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(data_get($row, $field) ? 'Activo' : 'Inactivo')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $attributes = $__attributesOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__attributesOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ed764111e345fc11534f121cfeb451)): ?>
<?php $component = $__componentOriginal51ed764111e345fc11534f121cfeb451; ?>
<?php unset($__componentOriginal51ed764111e345fc11534f121cfeb451); ?>
<?php endif; ?><?php else: ?><?php echo e(data_get($row, $field) ?? '—'); ?><?php endif; ?></td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php if($hasActions): ?>
                            <td class="cell-actions">
                                <?php if($canWrite): ?><a class="button button-small" href="<?php echo e(route('catalog.edit', ['module' => $module, 'id' => $row->id])); ?>" aria-label="Editar <?php echo e($definition['singular']); ?> <?php echo e($row->id); ?>"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
<?php endif; ?>Editar</a><?php endif; ?>
                                <?php if($module === 'pacientes' && $canWrite): ?>
                                    <a class="button button-small" href="<?php echo e(route('appointments.patient', $row->id)); ?>">Historial de citas</a>
                                <?php endif; ?>
                                <?php if($module === 'medicos' && auth()->user()->hasPermission('agenda.consultar')): ?>
                                    <a class="button button-small" href="<?php echo e(route('agenda.doctor', $row->id)); ?>">Ver agenda</a>
                                <?php endif; ?>
                                <?php if($module === 'usuarios' && $row->rol === 'Paciente' && $canWrite): ?>
                                    <a class="button button-small" href="<?php echo e(route('catalog.users.patient.edit', $row->id)); ?>">Vínculo con paciente</a>
                                <?php endif; ?>
                                <?php if($module === 'roles' && !in_array($row->nombre, \App\Modules\Shared\Services\CatalogoService::PROTECTED_ROLES, true) && (int) $row->id !== (int) auth()->user()->rol_id): ?>
                                    <a class="button button-small" href="<?php echo e(route('catalog.roles.confirm-destroy', $row->id)); ?>" aria-label="Eliminar rol <?php echo e($row->nombre); ?>">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody></table></div>
            <?php echo e($rows->links('pagination.default')); ?>

        <?php else: ?>
            <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['title' => 'No se encontraron registros','message' => request('q') ? 'Prueba con otro término de búsqueda o limpia el filtro.' : 'Los registros de este módulo aparecerán aquí.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'No se encontraron registros','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('q') ? 'Prueba con otro término de búsqueda o limpia el filtro.' : 'Los registros de este módulo aparecerán aquí.')]); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/catalog/index.blade.php ENDPATH**/ ?>