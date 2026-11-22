<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Sources\SourceResource;
use App\Models\Post;
use App\Models\Source;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
    }

    public function test_guests_are_sent_to_the_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_regular_users_cannot_open_the_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admins_can_open_every_section_of_the_panel(): void
    {
        $source = Source::factory()->create(['name' => 'Daily NK']);
        $post = Post::factory()->for($source)->create(['title' => 'Police officers clash at Hyesan Station']);

        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin')->assertOk();
        $this->get(SourceResource::getUrl('index'))->assertOk()->assertSee('Daily NK');
        $this->get(SourceResource::getUrl('edit', ['record' => $source]))->assertOk();
        $this->get(PostResource::getUrl('index'))->assertOk()->assertSee('Police officers clash at Hyesan Station');
        $this->get(PostResource::getUrl('create'))->assertOk();
        $this->get(PostResource::getUrl('edit', ['record' => $post]))->assertOk();
        $this->get(CategoryResource::getUrl('index'))->assertOk()->assertSee('propaganda');
    }

    public function test_admins_can_open_a_deleted_post_to_restore_it(): void
    {
        $post = Post::factory()->create();
        $post->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->get(PostResource::getUrl('edit', ['record' => $post]))
            ->assertOk();
    }

    public function test_the_make_admin_command_grants_and_revokes_access(): void
    {
        $user = User::factory()->create(['email' => 'luca@example.com']);

        $this->artisan('users:make-admin', ['email' => 'Luca@Example.com'])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('users:make-admin', ['email' => 'luca@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_the_make_admin_command_fails_for_unknown_emails(): void
    {
        $this->artisan('users:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }

    public function test_is_admin_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $this->assertFalse((bool) $user->fresh()->is_admin);
    }
}
