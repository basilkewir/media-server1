<?php $__env->startSection('title', 'Channels'); ?>

<?php $__env->startSection('content'); ?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 style="margin: 0;">Channels</h1>
    <a href="<?php echo e(route('admin.channels.create')); ?>" class="btn btn-primary">+ New Channel</a>
</div>

<div class="card">
    <?php if($channels->count()): ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Type</th>
                <th>Resolution</th>
                <th style="width: 280px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $channels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $channel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $activeStream = $channel->activeStream();
            ?>
            <tr>
                <td><strong><?php echo e($channel->name); ?></strong></td>
                <td><code><?php echo e($channel->slug); ?></code></td>
                <td>
                    <?php if($channel->is_live): ?>
                        <span style="color: var(--success); font-weight: 600;">● Live</span>
                    <?php elseif($activeStream && $activeStream->isFallback()): ?>
                        <span style="color: var(--warning); font-weight: 600;">● VOD Fallback</span>
                    <?php else: ?>
                        <span style="color: var(--text-muted);">● Offline</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($activeStream): ?>
                        <span class="badge badge-<?php echo e($activeStream->stream_type); ?>"><?php echo e(ucfirst($activeStream->stream_type)); ?></span>
                    <?php else: ?>
                        <span class="badge" style="background: #f1f5f9; color: var(--text-muted);">—</span>
                    <?php endif; ?>
                </td>
                <td><?php echo e($channel->resolution ?? '—'); ?></td>
                <td>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="<?php echo e(route('admin.channels.show', $channel)); ?>" class="btn" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; background: #f1f5f9; color: var(--text);">View</a>
                        <a href="<?php echo e(route('admin.channels.edit', $channel)); ?>" class="btn" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; background: #dbeafe; color: #1e40af;">Edit</a>
                        <a href="<?php echo e(route('stream.play', $channel->slug)); ?>" target="_blank" class="btn" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; background: #dcfce7; color: #166534;">Player</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color: var(--text-muted);">No channels found. <a href="<?php echo e(route('admin.channels.create')); ?>">Create your first channel</a>.</p>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\FT_Basil\Documents\streaming\media-server\resources\views/admin/channels/index.blade.php ENDPATH**/ ?>