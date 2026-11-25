<?php

namespace Database\Seeders;

use App\Models\Role;

class RoleSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [Role::ADMIN, Role::CUSTOMER];

        foreach ($roles as $role) {
            Role::create([
                'name' => $role,
            ]);
        }
    }
}
