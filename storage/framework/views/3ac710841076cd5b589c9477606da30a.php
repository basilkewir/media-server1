<?php $__env->startSection('title', 'New Channel'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <h1>Create Channel</h1>
    <p style="color: var(--text-muted); margin-top: -0.5rem;">Add a new streaming channel.</p>

    <form method="POST" action="<?php echo e(route('admin.channels.store')); ?>">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <input type="text" name="name" value="<?php echo e(old('name')); ?>" required>
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
            <label>Slug <span class="required">*</span></label>
            <input type="text" name="slug" value="<?php echo e(old('slug')); ?>" required placeholder="my-channel">
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
            <input type="text" name="description" value="<?php echo e(old('description')); ?>">
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
            <label>VOD Playlist URL <small>(fallback playlist)</small></label>
            <input type="text" name="vod_playlist_url" value="<?php echo e(old('vod_playlist_url')); ?>" placeholder="https://example.com/playlist.m3u8">
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
            <label>RTMP Push URL <small>(optional output target)</small></label>
            <input type="text" name="rtmp_push_url" value="<?php echo e(old('rtmp_push_url')); ?>" placeholder="rtmp://destination/live/key">
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
                <input type="number" name="bitrate_kbps" value="<?php echo e(old('bitrate_kbps')); ?>" min="32" max="50000" placeholder="3000">
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
                <input type="text" name="resolution" value="<?php echo e(old('resolution')); ?>" placeholder="1920x1080">
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

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_icecast_enabled" value="1" <?php echo e(old('is_icecast_enabled') ? 'checked' : ''); ?>>
                    Enable Icecast
                </label>
                <?php $__errorArgs = ['is_icecast_enabled'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small style="color: var(--danger);"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_relay_enabled" value="1" <?php echo e(old('is_relay_enabled') ? 'checked' : ''); ?>>
                    Enable Relay
                </label>
                <?php $__errorArgs = ['is_relay_enabled'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small style="color: var(--danger);"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Create Channel</button>
        <a href="<?php echo e(route('admin.channels.index')); ?>" style="margin-left: 0.75rem; color: var(--text-muted);">Cancel</a>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\FT_Basil\Documents\streaming\media-server\resources\views/admin/channels/create.blade.php ENDPATH**/ ?>