<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_only_roles_admin_and_settings(): void
    {
        $this->artisan('db:seed', ['--class' => 'ProductionSeeder', '--force' => true])
            ->expectsQuestion('Admin name', 'Owner')
            ->expectsQuestion('Admin email', 'owner@shopmoda.rs')
            ->expectsQuestion('Admin password (min. 10 characters)', 'longpassword1')
            ->expectsQuestion('Repeat the password', 'longpassword1')
            ->expectsQuestion('Email that receives new orders and the daily report', 'orders@shopmoda.rs')
            ->expectsQuestion('Email for contact messages (empty = order email)', null)
            ->expectsQuestion('Contact phone shown in the shop', '+381 60 000 000')
            ->expectsQuestion('Instagram profile URL (empty = no icon)', null)
            ->assertSuccessful();

        $this->assertEqualsCanonicalizing([Role::ADMIN, Role::CUSTOMER], Role::pluck('name')->all());

        $admin = User::sole();
        $this->assertSame('owner@shopmoda.rs', $admin->email);
        $this->assertSame(Role::ADMIN, $admin->role->name);
        $this->assertTrue(Hash::check('longpassword1', $admin->password));

        $this->assertSame('orders@shopmoda.rs', Setting::getValue(Setting::ORDER_EMAIL));
        $this->assertNull(Setting::getValue(Setting::CONTACT_EMAIL));
        $this->assertSame(0, Product::count());
    }

    public function test_mismatched_passwords_are_asked_again(): void
    {
        $this->artisan('db:seed', ['--class' => 'ProductionSeeder', '--force' => true])
            ->expectsQuestion('Admin name', 'Owner')
            ->expectsQuestion('Admin email', 'owner@shopmoda.rs')
            ->expectsQuestion('Admin password (min. 10 characters)', 'longpassword1')
            ->expectsQuestion('Repeat the password', 'different-one')
            ->expectsQuestion('Admin password (min. 10 characters)', 'longpassword2')
            ->expectsQuestion('Repeat the password', 'longpassword2')
            ->expectsQuestion('Email that receives new orders and the daily report', null)
            ->expectsQuestion('Email for contact messages (empty = order email)', null)
            ->expectsQuestion('Contact phone shown in the shop', null)
            ->expectsQuestion('Instagram profile URL (empty = no icon)', null)
            ->assertSuccessful();

        $this->assertTrue(Hash::check('longpassword2', User::sole()->password));
    }
}
