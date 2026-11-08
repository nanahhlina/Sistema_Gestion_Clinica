<?php if($paginator->hasPages()): ?>
    <nav class="pagination-bar" aria-label="Paginación">
        <p class="pagination-summary">Mostrando <?php echo e($paginator->firstItem()); ?>–<?php echo e($paginator->lastItem()); ?> <?php if(method_exists($paginator, 'total')): ?> de <?php echo e($paginator->total()); ?> registros <?php endif; ?></p>
        <ul class="pagination">
            <li><?php if($paginator->onFirstPage()): ?><span class="page-link" aria-disabled="true">Anterior</span><?php else: ?><a class="page-link" href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev">Anterior</a><?php endif; ?></li>
            <?php $__currentLoopData = $elements ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(is_string($element)): ?><li class="page-number"><span class="page-link" aria-hidden="true"><?php echo e($element); ?></span></li><?php endif; ?>
                <?php if(is_array($element)): ?><?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li class="page-number"><?php if($page == $paginator->currentPage()): ?><span class="page-link is-current" aria-current="page" aria-label="Página <?php echo e($page); ?>"><?php echo e($page); ?></span><?php else: ?><a class="page-link" href="<?php echo e($url); ?>" aria-label="Ir a la página <?php echo e($page); ?>"><?php echo e($page); ?></a><?php endif; ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <li><?php if($paginator->hasMorePages()): ?><a class="page-link" href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next">Siguiente</a><?php else: ?><span class="page-link" aria-disabled="true">Siguiente</span><?php endif; ?></li>
        </ul>
    </nav>
<?php endif; ?>
<?php /**PATH C:\Users\Dell\Documents\Universidad\INF229 - ING. REQ. Y MOD\Examen\Sist_Gestion_Clinica\resources\views/pagination/default.blade.php ENDPATH**/ ?>