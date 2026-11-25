<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends BaseSeeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * The order matters: roles before users, products before orders...
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            PromotionSeeder::class,
            CouponSeeder::class,
            OrderSeeder::class,
            SettingSeeder::class,
            UserLogSeeder::class,
        ]);
    }
}
