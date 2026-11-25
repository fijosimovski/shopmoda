<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;

class UserSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRoleId = Role::where('name', Role::ADMIN)->first()->id;
        $customerRoleId = Role::where('name', Role::CUSTOMER)->first()->id;

        // Admin account for testing: admin@shopmoda.test / admin123
        $this->createUser('Admin ShopModa', 'admin@shopmoda.test', 'admin123', $adminRoleId);

        // Customer account for testing: customer@shopmoda.test / customer123
        $this->createUser('Test Customer', 'customer@shopmoda.test', 'customer123', $customerRoleId);

        // More random customers (password: customer123)
        foreach (range(1, 10) as $i) {
            $this->createUser($this->faker->name, $this->faker->unique()->safeEmail, 'customer123', $customerRoleId);
        }
    }

    // role_id isn't in User::$fillable (for safety), so it's set separately.
    private function createUser(string $name, string $email, string $password, int $roleId): void
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => $password, // hashed automatically by the 'hashed' cast
        ]);
        $user->role_id = $roleId;
        $user->save();
    }
}
