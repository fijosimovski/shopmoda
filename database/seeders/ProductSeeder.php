<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModel;
use Illuminate\Support\Str;

class ProductSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     * Every model gets one product per colorway, e.g. the model "Air Jordan 1"
     * gets the products "Nike Air Jordan 1 Chicago", "Nike Air Jordan 1 Bred"...
     */
    public function run(): void
    {
        // [brand, category, model name, colorways]
        $models = [
            ['Nike', 'Running', 'Pegasus 41', ['Black White', 'Volt']],
            ['Nike', 'Lifestyle', 'Air Jordan 1', ['Chicago', 'Bred', 'Royal Blue']],
            ['Nike', 'Lifestyle', 'Air Max 90', ['Infrared', 'Triple White']],
            ['Nike', 'Lifestyle', 'Air Force 1', ['Triple White', 'Triple Black']],
            ['Nike', 'Basketball', 'LeBron XXII', ['Lakers']],
            ['Nike', 'Training', 'Metcon 9', ['Smoke Grey']],
            ['Adidas', 'Running', 'Ultraboost 5', ['Core Black', 'Cloud White']],
            ['Adidas', 'Lifestyle', 'Samba OG', ['White Green', 'Black Gum']],
            ['Adidas', 'Lifestyle', 'Gazelle', ['Collegiate Navy', 'Scarlet']],
            ['Adidas', 'Basketball', 'Harden Vol. 9', ['Solar Red']],
            ['New Balance', 'Running', 'Fresh Foam 1080', ['Blue Oasis']],
            ['New Balance', 'Lifestyle', '530', ['White Silver', 'Raincloud']],
            ['New Balance', 'Lifestyle', '9060', ['Sea Salt', 'Black Castlerock']],
            ['Puma', 'Running', 'Deviate Nitro 3', ['Sunset Glow']],
            ['Puma', 'Lifestyle', 'Suede Classic', ['Black', 'Red']],
            ['Puma', 'Basketball', 'MB.04', ['Toxic']],
            ['Asics', 'Running', 'Gel-Kayano 31', ['Black']],
            ['Asics', 'Running', 'Gel-Nimbus 27', ['White']],
            ['Asics', 'Lifestyle', 'Gel-1130', ['White Pure Silver', 'Cream']],
            ['Converse', 'Lifestyle', 'Chuck Taylor All Star', ['Black', 'Optical White', 'Red']],
            ['Converse', 'Skate', 'One Star Pro', ['Black']],
            ['Vans', 'Skate', 'Old Skool', ['Black White', 'Navy']],
            ['Vans', 'Skate', 'Sk8-Hi', ['Black']],
            ['Vans', 'Lifestyle', 'Authentic', ['True White']],
        ];

        $brands = Brand::all()->keyBy('name');
        $categories = Category::all()->keyBy('name');

        foreach ($models as [$brandName, $categoryName, $modelName, $colorways]) {
            $model = ProductModel::create([
                'brand_id' => $brands[$brandName]->id,
                'name' => $modelName,
                'slug' => Str::slug($brandName . ' ' . $modelName), // "nike-air-jordan-1"
                'image' => 'https://placehold.co/800x800/f8f9fa/212529?text=' . urlencode($modelName),
            ]);

            foreach ($colorways as $colorway) {
                $name = $brandName . ' ' . $modelName . ' ' . $colorway;
                $gender = $this->faker->randomElement(Product::GENDERS);

                $product = Product::create([
                    'brand_id' => $model->brand_id,
                    'model_id' => $model->id,
                    'category_id' => $categories[$categoryName]->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => $this->faker->paragraphs(2, true),
                    'gender' => $gender,
                    'discount_percent' => $this->faker->randomElement([0, 0, 0, 10, 15, 20, 30]),
                    'is_featured' => $this->faker->boolean(25),
                    'is_active' => true,
                    // spread "newest" over the last 3 months
                    'created_at' => now()->subDays(rand(0, 90)),
                ]);

                $this->addPrices($product);
                $this->addSizes($product, $gender);
                $this->addImages($product, $name);
            }
        }
    }

    // 1 to 3 prices: older ones are history, the last one is the current price.
    // They're created oldest first, so the newest row (highest id) is the current price.
    private function addPrices(Product $product): void
    {
        $price = $this->faker->numberBetween(70, 220) * 100 - 1; // e.g. 12999 (RSD)
        $count = rand(1, 3);

        for ($i = $count; $i >= 1; $i--) {
            $product->prices()->create([
                'price' => $price,
                'created_at' => now()->subDays($i * 30),
            ]);
            $price += $this->faker->randomElement([-1000, 1000, 2000]);
        }
    }

    private function addSizes(Product $product, string $gender): void
    {
        $sizes = match ($gender) {
            'women' => [36, 36.5, 37.5, 38, 38.5, 39, 40, 40.5, 41],
            'men' => [40, 40.5, 41, 42, 42.5, 43, 44, 44.5, 45, 46],
            default => [37.5, 38, 39, 40, 41, 42, 42.5, 43, 44, 45],
        };

        foreach ($sizes as $size) {
            $product->sizes()->create([
                'size' => $size,
                'stock' => $this->faker->randomElement([0, 2, 5, 8, 12, 20]), // some sizes sold out
            ]);
        }
    }

    // Placeholder images (online URLs), the first one is the main image.
    private function addImages(Product $product, string $name): void
    {
        foreach (range(1, 3) as $i) {
            $product->images()->create([
                'path' => 'https://placehold.co/800x800?text=' . urlencode($name . ' ' . $i),
                'is_main' => $i === 1,
                'sort_order' => $i,
            ]);
        }
    }
}
