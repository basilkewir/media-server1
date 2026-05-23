<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Channel;

class StorageQuotaService
{
    /**
     * Calculate total storage used by a user across all their channels.
     */
    public function calculateUserUsage(User $user): int
    {
        $channelIds = $user->manageableChannels()->pluck('id');

        return \App\Models\VodFile::whereIn('channel_id', $channelIds)
            ->where('source_type', 'upload')
            ->sum('size_bytes');
    }

    /**
     * Recalculate and update the user's storage usage.
     */
    public function recalculateUserStorage(User $user): void
    {
        $usedBytes = $this->calculateUserUsage($user);
        $user->update(['storage_used_bytes' => $usedBytes]);

        if ($sub = $user->activeSubscription()) {
            $sub->update(['storage_used_bytes' => $usedBytes]);
        }
    }

    /**
     * Check if user can upload a file of given size.
     */
    public function canUpload(User $user, int $fileSize): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $plan = $user->activePlan();
        if (!$plan) {
            return false;
        }

        $remaining = $plan->storage_quota_bytes - $user->storage_used_bytes;

        if ($remaining <= 0) {
            return false;
        }

        if ($fileSize > $plan->max_upload_bytes) {
            return false;
        }

        return $fileSize <= $remaining;
    }

    /**
     * Get detailed quota information for a user.
     */
    public function getQuotaInfo(User $user): array
    {
        // Admin bypass
        if ($user->isAdmin()) {
            return [
                'has_plan'            => true,
                'plan_name'           => 'Admin',
                'plan_tier'           => 'admin',
                'quota_bytes'         => 0,
                'used_bytes'          => 0,
                'remaining_bytes'     => PHP_INT_MAX,
                'quota_pct'           => 0,
                'quota_formatted'     => 'Unlimited',
                'used_formatted'      => '0 MB',
                'remaining_formatted' => 'Unlimited',
                'max_upload_bytes'    => 107374182400,
                'max_vod_files'       => 100000,
                'max_channels'        => 1000,
                'features'            => ['overlay', 'ticker', 'scheduling', 'priority_support', 'api_access'],
                'subscription_ends'   => null,
                'payment_status'      => 'none',
            ];
        }

        $plan = $user->activePlan();
        $subscription = $user->activeSubscription();

        if (!$plan) {
            return [
                'has_plan'            => false,
                'quota_bytes'         => 0,
                'used_bytes'          => 0,
                'remaining_bytes'     => 0,
                'quota_pct'           => 0,
                'quota_formatted'     => '0 MB',
                'used_formatted'      => '0 MB',
                'remaining_formatted' => '0 MB',
                'plan_name'           => 'No Plan',
                'max_upload_bytes'    => 0,
                'max_vod_files'       => 0,
            ];
        }

        $quota     = $plan->storage_quota_bytes;
        $used      = (int) $user->storage_used_bytes;
        $remaining = max(0, $quota - $used);
        $pct       = $quota > 0 ? min(100, round(($used / $quota) * 100, 1)) : 0;

        return [
            'has_plan'            => true,
            'plan_name'           => $plan->name,
            'plan_tier'           => $plan->tier,
            'quota_bytes'         => $quota,
            'used_bytes'          => $used,
            'remaining_bytes'     => $remaining,
            'quota_pct'           => $pct,
            'quota_formatted'     => $this->formatBytes($quota),
            'used_formatted'      => $this->formatBytes($used),
            'remaining_formatted' => $this->formatBytes($remaining),
            'max_upload_bytes'    => $plan->max_upload_bytes,
            'max_vod_files'       => $plan->max_vod_files,
            'max_channels'        => $plan->max_channels,
            'features'            => $plan->features ?? [],
            'subscription_ends'   => $subscription?->ends_at?->toIso8601String(),
            'payment_status'      => $subscription?->payment_status,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1024, 2) . ' KB';
    }
}
