<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Running', 'Lifestyle', 'Basketball', 'Skate', 'Training'];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'slug' => Str::slug($category),
            ]);
        }
    }
}
