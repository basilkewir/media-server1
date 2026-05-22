<?php $__env->startSection('title', 'Edit ' . $channel->name); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <h1>Edit Channel</h1>
    <p style="color: var(--text-muted); margin-top: -0.5rem;"><?php echo e($channel->name); ?></p>

    <form method="POST" action="<?php echo e(route('admin.channels.update', $channel)); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="<?php echo e(old('name', $channel->name)); ?>">
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small style="color: var(--danger);"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" value="<?php echo e(old('slug', $channel->slug)); ?>">
            <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small style="color: var(--danger);"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" value="<?php echo e(old('description', $channel->description)); ?>">
            <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small style="color: var(--danger);"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="form-group">
            <label>VOD Playlist URL</label>
            <input type="text" name="vod_playlist_url" value="<?php echo e(old('vod_playlist_url', $channel->vod_playlist_url)); ?>">
            <?php $__errorArgs = ['vod_playlist_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small style="color: var(--danger);"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="form-group">
            <label>RTMP Push URL</label>
            <input type="text" name="rtmp_push_url" value="<?php echo e(old('rtmp_push_url', $channel->rtmp_push_url)); ?>">
            <?php $__errorArgs = ['rtmp_push_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small style="color: var(--danger);"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Bitrate (kbps)</label>
                <input type="number" name="bitrate_kbps" value="<?php echo e(old('bitrate_kbps', $channel->bitrate_kbps)); ?>" min="32" max="50000">
                <?php $__errorArgs = ['bitrate_kbps'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small style="color: var(--danger);"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label>Resolution</label>
                <input type="text" name="resolution" value="<?php echo e(old('resolution', $channel->resolution)); ?>">
                <?php $__errorArgs = ['resolution'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small style="color: var(--danger);"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo e(old('is_active', $channel->is_active) ? 'checked' : ''); ?>>
                    Active
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_icecast_enabled" value="1" <?php echo e(old('is_icecast_enabled', $channel->is_icecast_enabled) ? 'checked' : ''); ?>>
                    Icecast
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_relay_enabled" value="1" <?php echo e(old('is_relay_enabled', $channel->is_relay_enabled) ? 'checked' : ''); ?>>
                    Relay
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Channel</button>
        <a href="<?php echo e(route('admin.channels.index')); ?>" style="margin-left: 0.75rem; color: var(--text-muted);">Cancel</a>
    </form>
</div>

<?php
$activeStream = $channel->activeStream();
?>

<div class="card">
    <h2>Stream Control</h2>

    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
        <?php if(!$channel->is_live): ?>
        <div style="flex: 1; min-width: 280px;">
            <form method="POST" action="<?php echo e(route('admin.channels.start', $channel)); ?>">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label>Source URL <span class="required">*</span></label>
                    <input type="text" name="push_url" placeholder="rtmp://source/live/stream" required>
                </div>
                <button type="submit" class="btn btn-primary" style="background: var(--success);">▶ Start Stream</button>
            </form>
        </div>
        <?php else: ?>
        <div style="flex: 1; min-width: 200px;">
            <form method="POST" action="<?php echo e(route('admin.channels.stop', $channel)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-primary" style="background: var(--danger);" onclick="return confirm('Stop the stream?')">⏹ Stop Stream</button>
            </form>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <form method="POST" action="<?php echo e(route('admin.channels.fallback', $channel)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-primary" style="background: var(--warning); color: #1e293b;" onclick="return confirm('Switch to VOD fallback?')">⏸ VOD Fallback</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if($activeStream && $activeStream->isFallback()): ?>
        <div style="flex: 1; min-width: 280px;">
            <form method="POST" action="<?php echo e(route('admin.channels.recover', $channel)); ?>">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label>Live Source URL <span class="required">*</span></label>
                    <input type="text" name="push_url" placeholder="rtmp://source/live/stream" required>
                </div>
                <button type="submit" class="btn btn-primary">↻ Recover to Live</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="<?php echo e(route('admin.channels.show', $channel)); ?>" class="btn" style="background: #f1f5f9; color: var(--text);">View Details</a>
        <a href="<?php echo e(route('admin.channels.events', $channel)); ?>" class="btn" style="background: #f1f5f9; color: var(--text);">Event Log</a>
        <a href="<?php echo e(route('stream.play', $channel->slug)); ?>" target="_blank" class="btn" style="background: #dbeafe; color: #1e40af;">Open Player</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\FT_Basil\Documents\streaming\media-server\resources\views/admin/channels/edit.blade.php ENDPATH**/ ?>