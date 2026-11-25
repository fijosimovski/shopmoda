<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;

class OrderSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     * Fake orders, so "best sellers" and the admin order list have data.
     * Seeded orders don't lower the stock - only real orders will.
     */
    public function run(): void
    {
        $customers = User::whereHas('role', function ($query) {
            $query->where('name', Role::CUSTOMER);
        })->get();

        $products = Product::with(['currentPrice', 'sizes'])->get();

        foreach (range(1, 30) as $i) {
            // about 1 in 3 orders is from a guest
            $user = $this->faker->boolean(65) ? $customers->random() : null;
            $createdAt = now()->subDays(rand(0, 60))->subHours(rand(0, 23));

            $order = Order::create([
                'user_id' => $user?->id,
                'name' => $user?->name ?? $this->faker->name,
                'email' => $user?->email ?? $this->faker->safeEmail,
                'phone' => $this->faker->numerify('06########'),
                'address' => $this->faker->streetAddress,
                'city' => $this->faker->randomElement(['Beograd', 'Novi Sad', 'Niš', 'Kragujevac', 'Subotica']),
                'note' => $this->faker->boolean(20) ? $this->faker->sentence : null,
                'total' => 0, // calculated below from the items
                'status' => $this->faker->randomElement(Order::STATUSES),
                'ip_address' => $this->faker->ipv4,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $total = 0;

            foreach ($products->random(rand(1, 3)) as $product) {
                $quantity = rand(1, 2);
                $unitPrice = $product->finalPrice();

                $order->items()->create([
                    'product_id' => $product->id,
                    'size' => $product->sizes->random()->size,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);

                $total += $unitPrice * $quantity;
            }

            $order->update(['subtotal' => $total, 'total' => $total]);
        }
    }
}
