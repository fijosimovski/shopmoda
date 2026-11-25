<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Models\UserLog;

class UserLogSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     * Example log rows, so the admin "user logs" page has data.
     * In the real app, controllers will call UserLog::record(...).
     */
    public function run(): void
    {
        // Every user registered and logged in a few times.
        foreach (User::all() as $user) {
            $registeredAt = now()->subDays(rand(60, 90));
            $this->log($user->id, 'register', 'Registered', $user, $registeredAt);

            foreach (range(1, rand(1, 5)) as $i) {
                $this->log($user->id, 'login', 'Logged in', null, now()->subDays(rand(0, 59)));
            }
        }

        // Every seeded order was "placed".
        foreach (Order::all() as $order) {
            $this->log($order->user_id, 'order.created', 'Order #' . $order->id . ' placed', $order, $order->created_at, $order->ip_address);
        }
    }

    private function log(?int $userId, string $action, string $description, $subject, $date, ?string $ip = null): void
    {
        UserLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $ip ?? $this->faker->ipv4,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
