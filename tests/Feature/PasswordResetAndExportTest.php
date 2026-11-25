<?php

namespace Tests\Feature;

use App\Mail\DailyOrdersReportMail;
use App\Mail\PasswordResetCodeMail;
use App\Models\Order;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetAndExportTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = Role::CUSTOMER): User
    {
        $user = new User(['name' => 'Test User', 'email' => $role . '@example.test', 'password' => 'Secret123']);
        $user->role_id = Role::firstOrCreate(['name' => $role])->id;
        $user->save();

        return $user;
    }

    private function order(string $createdAt): Order
    {
        $order = Order::create([
            'name' => 'Pera Peric', 'email' => 'pera@example.test', 'phone' => '0601234567', 'address' => 'Ulica 1',
            'city' => 'Beograd', 'subtotal' => 10000, 'discount_total' => 0, 'total' => 10000, 'status' => Order::STATUS_NEW,
        ]);
        $order->created_at = $createdAt;
        $order->save();

        return $order;
    }

    // Sends the reset form and returns the code from the faked email.
    private function requestCode(string $email): ?string
    {
        $code = null;
        $this->post('/forgot-password', ['email' => $email])->assertRedirect(route('password.reset'));
        Mail::assertSent(PasswordResetCodeMail::class, function ($mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        return $code;
    }

    public function test_password_can_be_reset_with_the_emailed_code(): void
    {
        Mail::fake();
        $user = $this->user();
        $code = $this->requestCode($user->email);

        $this->post('/reset-password', ['email' => $user->email, 'code' => $code, 'password' => 'NewSecret456', 'password_confirmation' => 'NewSecret456'])
            ->assertRedirect(route('login'));

        $this->assertTrue(Auth::validate(['email' => $user->email, 'password' => 'NewSecret456']));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        // The same code can't be used twice.
        $this->post('/reset-password', ['email' => $user->email, 'code' => $code, 'password' => 'Other789Xy', 'password_confirmation' => 'Other789Xy'])
            ->assertSessionHasErrors('code');
    }

    public function test_unknown_email_gets_the_same_answer_but_no_mail(): void
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.test'])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHas('success');

        Mail::assertNothingSent();
    }

    public function test_code_is_thrown_away_after_too_many_wrong_tries(): void
    {
        Mail::fake();
        $user = $this->user();
        $code = $this->requestCode($user->email);
        $wrong = $code === '111111' ? '222222' : '111111';

        for ($i = 0; $i < 5; $i++) {
            $this->post('/reset-password', ['email' => $user->email, 'code' => $wrong, 'password' => 'NewSecret456', 'password_confirmation' => 'NewSecret456']);
        }

        // Even the right code no longer works.
        $this->post('/reset-password', ['email' => $user->email, 'code' => $code, 'password' => 'NewSecret456', 'password_confirmation' => 'NewSecret456'])
            ->assertSessionHasErrors('code');
        $this->assertFalse(Auth::validate(['email' => $user->email, 'password' => 'NewSecret456']));
    }

    public function test_admin_can_export_orders_as_excel_and_text(): void
    {
        $this->order(now()->subHours(2));
        $this->order(now()->subDays(3)); // outside "last 24 hours"
        $admin = $this->user(Role::ADMIN);

        $this->actingAs($admin)->get('/admin/orders/export?range=24h&format=xlsx')
            ->assertOk()
            ->assertDownload();

        $text = $this->actingAs($admin)->get('/admin/orders/export?range=24h&format=txt')->assertOk()->getContent();
        $this->assertStringContainsString('Orders: 1 ', $text);
        $this->assertStringContainsString('Pera Peric', $text);

        $this->actingAs($admin)->get('/admin/orders/export?range=1y&format=xlsx')->assertSessionHasErrors('range');
        $this->actingAs($this->user())->get('/admin/orders/export?range=24h&format=txt')->assertForbidden();
    }

    public function test_daily_report_is_mailed_only_when_there_are_orders(): void
    {
        Mail::fake();
        Setting::setValue(Setting::ORDER_EMAIL, 'shop@example.test');

        $this->artisan('orders:daily-report')->assertSuccessful();
        Mail::assertNothingSent();

        $this->order(today()->subDay()->setTime(14, 30));
        $this->order(today()->setTime(0, 5)); // today: belongs to tomorrow's report

        $this->artisan('orders:daily-report')->assertSuccessful();
        Mail::assertSent(DailyOrdersReportMail::class, fn ($mail) => $mail->hasTo('shop@example.test') && $mail->orders->count() === 1);
    }
}
