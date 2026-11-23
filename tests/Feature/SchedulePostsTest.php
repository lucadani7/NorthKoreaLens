<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use Carbon\CarbonImmutable;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulePostsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
        // 15:00 in Bucharest (UTC+2 in November): today's 10:00 has passed.
        $this->travelTo(CarbonImmutable::parse('2026-11-23 13:00:00', 'UTC'));
    }

    private function draft(string $title, ?Source $source = null): Post
    {
        return Post::factory()->create([
            'category_id' => Category::where('slug', 'curiosities')->value('id'),
            'source_id' => $source?->id,
            'guid' => 'nklens:'.$title,
            'title' => $title,
            'published_at' => null,
        ]);
    }

    /** @return list<string> */
    private function localDates(): array
    {
        return Post::query()->whereNotNull('published_at')->orderBy('published_at')->get()
            ->map(fn (Post $post) => $post->published_at?->setTimezone('Europe/Bucharest')->format('Y-m-d H:i'))
            ->values()->all();
    }

    public function test_drafts_get_a_few_dates_a_day_from_tomorrow(): void
    {
        foreach (range(1, 3) as $i) {
            $this->draft("Curiosity {$i}");
        }

        $this->artisan('posts:schedule', ['category' => 'curiosities', '--per-day' => 2])->assertSuccessful();

        $this->assertSame(['2026-11-24 10:00', '2026-11-24 10:01', '2026-11-25 10:00'], $this->localDates());
        $this->assertSame(0, Post::feed()->count());

        $this->travelTo(CarbonImmutable::parse('2026-11-24 08:30:00', 'UTC'));
        $this->assertSame(2, Post::feed()->count());
    }

    public function test_new_drafts_continue_after_the_scheduled_ones(): void
    {
        foreach (range(1, 3) as $i) {
            $this->draft("Curiosity {$i}");
        }
        $this->artisan('posts:schedule', ['category' => 'curiosities', '--per-day' => 2])->assertSuccessful();

        $this->draft('Curiosity 4');
        $this->draft('Curiosity 5');
        $this->artisan('posts:schedule', ['category' => 'curiosities', '--per-day' => 2])->assertSuccessful();

        $this->assertSame(
            ['2026-11-24 10:00', '2026-11-24 10:01', '2026-11-25 10:00', '2026-11-25 10:01', '2026-11-26 10:00'],
            $this->localDates(),
        );
    }

    public function test_feed_drafts_and_dry_runs_are_left_alone(): void
    {
        $this->draft('From a feed', Source::factory()->create());
        $team = $this->draft('Ours');

        $this->artisan('posts:schedule', ['category' => 'curiosities', '--dry-run' => true])
            ->expectsOutputToContain('2026-11-24 10:00  Ours')
            ->assertSuccessful();
        $this->assertNull($team->fresh()?->published_at);

        $this->artisan('posts:schedule', ['category' => 'curiosities'])->assertSuccessful();
        $this->assertSame(['2026-11-24 10:00'], $this->localDates());
    }
}
