<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Promotion;

class PromotionSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     * Running, upcoming and ended examples of every promotion type. Safe to run again (matched by slug).
     */
    public function run(): void
    {
        $brand = fn (string $slug) => Brand::where('slug', $slug)->value('id');
        $category = fn (string $slug) => Category::where('slug', $slug)->value('id');

        $promotions = [
            [
                'name' => 'Black Friday Week',
                'color' => '1a1a2e',
                'description' => 'Every Nike sneaker 20% off for one week only. The discount is already in the price.',
                'type' => Promotion::TYPE_PERCENT,
                'value' => 20,
                'priority' => 10,
                'starts_at' => now()->subDay()->startOfDay(),
                'ends_at' => now()->addDays(8)->endOfDay(),
                'targets' => [Brand::class => [$brand('nike')]],
            ],
            [
                'name' => 'Second Pair Deal',
                'color' => '0f3d3e',
                'description' => 'Buy two pairs of running shoes and the cheaper one is 30% off. Mix and match any models.',
                'type' => Promotion::TYPE_NTH_ITEM,
                'value' => 30,
                'nth' => 2,
                'priority' => 5,
                'is_stackable' => true,
                'starts_at' => now()->subDays(2)->startOfDay(),
                'ends_at' => now()->addDays(14)->endOfDay(),
                'targets' => [Category::class => [$category('running')]],
            ],
            [
                'name' => 'Spend & Save',
                'color' => '3d1f47',
                'description' => 'Spend 25.000 RSD or more and get 2.500 RSD off your whole order.',
                'type' => Promotion::TYPE_CART_THRESHOLD,
                'value' => 2500,
                'min_cart_total' => 25000,
                'applies_to_all' => true,
                'is_stackable' => true,
                'starts_at' => now()->subDays(3)->startOfDay(),
                'ends_at' => now()->addDays(10)->endOfDay(),
            ],
            [
                'name' => 'Winter Kickoff',
                'color' => '1d3557',
                'description' => 'Puma and Vans 15% off to start the winter.',
                'type' => Promotion::TYPE_PERCENT,
                'value' => 15,
                'starts_at' => now()->addDays(10)->startOfDay(),
                'ends_at' => now()->addDays(20)->endOfDay(),
                'targets' => [Brand::class => [$brand('puma'), $brand('vans')]],
            ],
            [
                'name' => 'Summer Sale',
                'color' => '6b3e26',
                'description' => 'Lifestyle sneakers 1.500 RSD off.',
                'type' => Promotion::TYPE_FIXED,
                'value' => 1500,
                'starts_at' => now()->subMonths(4)->startOfDay(),
                'ends_at' => now()->subMonths(3)->endOfDay(),
                'targets' => [Category::class => [$category('lifestyle')]],
            ],
        ];

        foreach ($promotions as $data) {
            $targets = $data['targets'] ?? [];
            $color = $data['color'];
            unset($data['targets'], $data['color']);

            $slug = \Illuminate\Support\Str::slug($data['name']);

            $promotion = Promotion::updateOrCreate(['slug' => $slug], $data + [
                // Placeholder banner (online URL), replace it in Admin > Promotions.
                'image' => 'https://placehold.co/1920x720/' . $color . '/' . $color . '?text=%20',
                'is_active' => true,
            ]);

            $promotion->syncTargets(array_map(fn ($ids) => array_filter($ids), $targets));
        }
    }
}
