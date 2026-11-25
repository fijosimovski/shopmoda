<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;

class CouponSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     * Example coupons for each option. Safe to run again (matched by code).
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'description' => '10% off the first order, max 3.000 RSD',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 10,
                'max_discount' => 3000,
                'usage_limit_per_customer' => 1,
                'first_order_only' => true,
            ],
            [
                'code' => 'SAVE1500',
                'description' => '1.500 RSD off orders over 12.000 RSD, full-price items only',
                'type' => Coupon::TYPE_FIXED,
                'value' => 1500,
                'min_order_total' => 12000,
                'exclude_sale_items' => true,
                'ends_at' => now()->addMonth()->endOfDay(),
            ],
            [
                'code' => 'RUN15',
                'description' => '15% off running shoes',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 15,
                'applies_to_all' => false,
                'usage_limit' => 100,
                'targets' => [Category::class => [Category::where('slug', 'running')->value('id')]],
            ],
            [
                'code' => 'VIP-3000-ONCE',
                'description' => 'Single-use: 3.000 RSD off, works for one order only',
                'type' => Coupon::TYPE_FIXED,
                'value' => 3000,
                'usage_limit' => 1,
                'usage_limit_per_customer' => 1,
            ],
            [
                'code' => 'SUMMER5',
                'description' => 'Expired example',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 5,
                'starts_at' => now()->subMonths(4),
                'ends_at' => now()->subMonths(3),
            ],
        ];

        foreach ($coupons as $data) {
            $targets = $data['targets'] ?? [];
            unset($data['targets']);

            $coupon = Coupon::updateOrCreate(['code' => $data['code']], $data + ['is_active' => true]);
            $coupon->syncTargets(array_map(fn ($ids) => array_filter($ids), $targets));
        }
    }
}
