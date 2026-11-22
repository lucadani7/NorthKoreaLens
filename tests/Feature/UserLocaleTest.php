<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_accounts_keep_the_language_of_the_sign_up_page(): void
    {
        Notification::fake();

        $this->withCookie('locale', 'nb')->post(route('register.store'), [
            'name' => 'Kari Nordmann',
            'email' => 'kari@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'kari@example.com')->firstOrFail();

        $this->assertSame('nb', $user->locale);
        Notification::assertSentTo($user, VerifyEmail::class, fn ($notification, $channels, $notifiable, $locale) => $locale === 'nb');
    }

    public function test_signed_in_users_see_their_own_language_on_any_device(): void
    {
        $user = User::factory()->create(['locale' => 'ro']);

        $this->actingAs($user)
            ->withHeader('Accept-Language', 'de-DE')
            ->withCookie('locale', 'ko')
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'ro'));
    }

    public function test_users_without_a_language_fall_back_to_the_usual_choice(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $this->actingAs($user)
            ->withHeader('Accept-Language', 'fr-FR')
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'fr'));
    }

    public function test_picking_a_language_while_signed_in_saves_it_in_the_account(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->from(route('appearance.edit'))
            ->post(route('locale.update'), ['locale' => 'it'])
            ->assertRedirect(route('appearance.edit'));

        $this->assertSame('it', $user->fresh()?->locale);
    }

    public function test_emails_use_the_language_of_the_account(): void
    {
        Notification::fake();

        $user = User::factory()->create(['locale' => 'ko']);

        // Requested from a browser in English, but the email follows the account.
        $this->withHeader('Accept-Language', 'en-US')
            ->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, fn ($notification, $channels, $notifiable, $locale) => $locale === 'ko');
    }

    public function test_a_language_we_no_longer_offer_is_ignored(): void
    {
        $user = User::factory()->make(['locale' => 'xx']);

        $this->assertNull($user->preferredLocale());
    }
}
