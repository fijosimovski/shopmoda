<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Support\Str;

class BrandSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = ['Nike', 'Adidas', 'New Balance', 'Puma', 'Asics', 'Converse', 'Vans'];

        foreach ($brands as $brand) {
            Brand::create([
                'name' => $brand,
                'slug' => Str::slug($brand), // "New Balance" -> "new-balance"
                // Placeholder image (online URL), replace it in Admin > Brands.
                'image' => 'https://placehold.co/1200x600/212529/ffc107?text=' . urlencode($brand),
            ]);
        }
    }
}
