<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'               => 'Free',
                'slug'               => 'free',
                'description'        => 'Free tier with 500 MB storage. Perfect for getting started.',
                'tier'               => Plan::TIER_FREE,
                'storage_quota_bytes'=> 500 * 1048576,  // 500 MB
                'max_channels'       => 1,
                'max_vod_files'      => 10,
                'max_upload_bytes'   => 500 * 1048576,
                'features'           => json_encode(['basic_ingest']),
                'price_cents'        => 0,
                'currency'           => 'USD',
                'billing_interval'   => 'month',
                'is_active'          => true,
                'sort_order'         => 1,
            ],
            [
                'name'               => 'Basic',
                'slug'               => 'basic',
                'description'        => '10 GB storage, 3 channels, overlay graphics, VOD scheduling.',
                'tier'               => Plan::TIER_BASIC,
                'storage_quota_bytes'=> 10 * 1073741824,
                'max_channels'       => 3,
                'max_vod_files'      => 50,
                'max_upload_bytes'   => 2 * 1073741824,
                'features'           => json_encode(['overlay', 'ticker', 'scheduling']),
                'price_cents'        => 999,  // $9.99
                'currency'           => 'USD',
                'billing_interval'   => 'month',
                'is_active'          => true,
                'sort_order'         => 2,
            ],
            [
                'name'               => 'Pro',
                'slug'               => 'pro',
                'description'        => '100 GB storage, 10 channels, all features, priority support.',
                'tier'               => Plan::TIER_PRO,
                'storage_quota_bytes'=> 100 * 1073741824,
                'max_channels'       => 10,
                'max_vod_files'      => 200,
                'max_upload_bytes'   => 5 * 1073741824,
                'features'           => json_encode(['overlay', 'ticker', 'scheduling', 'priority_support', 'api_access']),
                'price_cents'        => 2999,  // $29.99
                'currency'           => 'USD',
                'billing_interval'   => 'month',
                'is_active'          => true,
                'sort_order'         => 3,
            ],
            [
                'name'               => 'Enterprise',
                'slug'               => 'enterprise',
                'description'        => 'Unlimited storage, unlimited channels, dedicated support.',
                'tier'               => Plan::TIER_ENTERPRISE,
                'storage_quota_bytes'=> 1024 * 1073741824,  // 1 TB
                'max_channels'       => 100,
                'max_vod_files'      => 1000,
                'max_upload_bytes'   => 20 * 1073741824,
                'features'           => json_encode(['overlay', 'ticker', 'scheduling', 'priority_support', 'api_access']),
                'price_cents'        => 9999,  // $99.99
                'currency'           => 'USD',
                'billing_interval'   => 'month',
                'is_active'          => true,
                'sort_order'         => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
