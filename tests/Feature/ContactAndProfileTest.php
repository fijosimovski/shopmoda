<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\Announcement;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactAndProfileTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        $role = Role::firstOrCreate(['name' => Role::CUSTOMER]);
        $user = new User(['name' => 'Test Customer', 'email' => 'customer@example.test', 'password' => 'Secret123']);
        $user->role_id = $role->id;
        $user->save();

        return $user;
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/contact')->assertRedirect(route('login'));
    }

    public function test_message_is_saved_and_mailed_to_contact_email(): void
    {
        Mail::fake();
        Setting::setValue(Setting::CONTACT_EMAIL, 'support@example.test');

        $this->actingAs($this->customer())
            ->post('/contact', ['subject' => 'Exchange', 'message' => 'Can I get a bigger size please?'])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', ['subject' => 'Exchange', 'email' => 'customer@example.test']);
        Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasTo('support@example.test') && $mail->hasReplyTo('customer@example.test'));
    }

    public function test_honeypot_and_rate_limit_block_messages(): void
    {
        Mail::fake();
        $user = $this->customer();
        $data = ['subject' => 'Hello', 'message' => 'A message that is long enough.'];

        $this->actingAs($user)->post('/contact', $data + ['website' => 'spam'])->assertSessionHas('error');
        $this->assertSame(0, ContactMessage::count());

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->post('/contact', $data)->assertSessionHas('success');
        }
        $this->actingAs($user)->post('/contact', $data)->assertSessionHas('error');
        $this->assertSame(3, ContactMessage::count());
    }

    // A password manager that fills the hidden field with the email is a person: the login goes through.
    public function test_login_works_when_the_browser_autofills_the_honeypot_with_the_email(): void
    {
        $user = $this->customer();

        $this->post('/login', ['email' => $user->email, 'password' => 'Secret123', 'website' => $user->email])
            ->assertSessionMissing('error');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_blocked_when_the_honeypot_has_anything_else(): void
    {
        $user = $this->customer();

        $this->post('/login', ['email' => $user->email, 'password' => 'Secret123', 'website' => 'http://spam.example'])
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_profile_ignores_email_changes(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->put('/account/profile', ['name' => 'New Name', 'email' => 'hacker@example.test'])
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('customer@example.test', $user->email);
    }

    public function test_orders_get_a_random_number(): void
    {
        $order = Order::create([
            'name' => 'A B', 'email' => 'a@example.test', 'phone' => '0601234567', 'address' => 'Street 1', 'city' => 'Belgrade',
            'subtotal' => 100, 'discount_total' => 0, 'total' => 100, 'status' => Order::STATUS_NEW,
        ]);

        $this->assertMatchesRegularExpression('/^SM-[A-HJ-NP-Z2-9]{8}$/', $order->number);
    }

    public function test_announcement_bar_respects_show_on(): void
    {
        foreach ([['Home only', Announcement::SHOW_HOME], ['Everywhere', Announcement::SHOW_ALL]] as [$title, $showOn]) {
            Announcement::create([
                'type' => Announcement::TYPE_DROP, 'title' => $title, 'show_on' => $showOn,
                'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'is_active' => true,
            ]);
        }

        $this->get('/')->assertSee('Home only')->assertSee('Everywhere');
        $this->get('/products')->assertDontSee('Home only')->assertSee('Everywhere');
    }
}
