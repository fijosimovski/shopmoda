<?php

namespace Database\Seeders;

use App\Models\Setting;

class SettingSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // The email address that receives new orders (changeable in the admin panel).
        Setting::setValue(Setting::ORDER_EMAIL, 'orders@shopmoda.test');
        // Contact page: where messages go and the phone number shown.
        Setting::setValue(Setting::CONTACT_EMAIL, 'support@shopmoda.test');
        Setting::setValue(Setting::CONTACT_PHONE, '+381 11 123 4567');
    }
}
