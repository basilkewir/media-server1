<?php $__env->startSection('title', 'Access Codes'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <h1>Access Codes</h1>

    <?php if($codes->count()): ?>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Duration</th>
                <th>Uses</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $codes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><code><?php echo e($code->code); ?></code></td>
                <td><span class="badge badge-<?php echo e($code->type); ?>"><?php echo e($code->getTypeLabel()); ?></span></td>
                <td><?php echo e($code->duration_days); ?> days</td>
                <td><?php echo e($code->uses_count); ?> / <?php echo e($code->max_uses); ?></td>
                <td>
                    <?php if($code->isValid()): ?>
                        <span style="color: var(--success); font-weight: 600;">Active</span>
                    <?php else: ?>
                        <span style="color: var(--text-muted);">Inactive</span>
                    <?php endif; ?>
                </td>
                <td><?php echo e($code->created_at->format('Y-m-d')); ?></td>
                <td>
                    <form method="POST" action="<?php echo e(route('admin.access-codes.destroy', $code)); ?>" style="display:inline;" onsubmit="return confirm('Deactivate this code?')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:0.875rem;">Deactivate</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
        <?php echo e($codes->links()); ?>

    </div>
    <?php else: ?>
    <p style="color: var(--text-muted);">No access codes found. <a href="<?php echo e(route('admin.access-codes.create')); ?>">Generate some</a>.</p>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\FT_Basil\Documents\streaming\media-server\resources\views/admin/access-codes/index.blade.php ENDPATH**/ ?>