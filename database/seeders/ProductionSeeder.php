<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

// For the live server only: roles, one admin account and the shop settings. No demo products, orders, users or logs.
//   php artisan db:seed --class=ProductionSeeder --force
// It asks for the admin's name, email and password in the terminal (the password is not shown while typing),
// so no password is ever written in the code or in .env.
// Safe to run again: existing roles/settings are kept, and an existing admin email is left untouched.
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->roles();
        $this->admin();
        $this->settings();

        $this->command->info('Done. Log in at /login, then fill in Admin > Settings and the catalog.');
    }

    private function roles(): void
    {
        foreach ([Role::ADMIN, Role::CUSTOMER] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }

    private function admin(): void
    {
        $name = $this->command->ask('Admin name', 'ShopModa Admin');

        $email = $this->askValid('Admin email', 'required|email', fn () => $this->command->ask('Admin email'));

        if (User::where('email', $email)->exists()) {
            $this->command->warn("A user with {$email} already exists, it was not changed.");
            return;
        }

        $password = $this->askValid('Password', 'required|string|min:10', function () {
            $password = $this->command->secret('Admin password (min. 10 characters)');
            $repeat = $this->command->secret('Repeat the password');

            if ($password !== $repeat) {
                $this->command->error('The passwords do not match.');
                return null;
            }

            return $password;
        });

        // role_id isn't in User::$fillable (for safety), so it's set separately.
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => $password, // hashed automatically by the 'hashed' cast
        ]);
        $user->role_id = Role::where('name', Role::ADMIN)->value('id');
        $user->save();

        $this->command->info("Admin {$email} created.");
    }

    // Order email, contact email and phone. Enter = skip (it can be filled in later in Admin > Settings).
    private function settings(): void
    {
        $settings = [
            Setting::ORDER_EMAIL => ['Email that receives new orders and the daily report', 'nullable|email'],
            Setting::CONTACT_EMAIL => ['Email for contact messages (empty = order email)', 'nullable|email'],
            Setting::CONTACT_PHONE => ['Contact phone shown in the shop', 'nullable|string|max:50'],
            Setting::INSTAGRAM_URL => ['Instagram profile URL (empty = no icon)', 'nullable|url'],
        ];

        foreach ($settings as $key => [$question, $rules]) {
            $current = Setting::getValue($key);
            $value = $this->askValid($question, $rules, fn () => $this->command->ask($question, $current));

            Setting::setValue($key, $value === '' ? null : $value);
        }
    }

    // Asks again until the answer passes the validation rules (max. 5 times).
    private function askValid(string $label, string $rules, callable $ask): ?string
    {
        for ($try = 0; $try < 5; $try++) {
            $value = $ask();

            if ($value === null && !str_contains($rules, 'nullable')) {
                continue;
            }

            $validator = Validator::make(['value' => $value], ['value' => $rules]);

            if ($validator->passes()) {
                return $value;
            }

            $this->command->error($label . ': ' . $validator->errors()->first('value'));
        }

        throw new RuntimeException("No valid answer for \"{$label}\", nothing more was saved. Run the seeder again.");
    }
}
