<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Topic;
use Database\Seeders\CategorySeeder;
use Database\Seeders\TopicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamPostsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CategorySeeder::class, TopicSeeder::class]);
    }

    private function curiosity(string $title = 'Seoul was the first capital'): Post
    {
        return Post::factory()->ownContent()
            ->for(Category::where('slug', 'curiosities')->firstOrFail())
            ->create(['title' => $title, 'body' => 'English text.', 'language' => 'en']);
    }

    private function topic(string $slug): Topic
    {
        return Topic::where('slug', $slug)->firstOrFail();
    }

    public function test_readers_get_the_post_in_their_language(): void
    {
        $post = $this->curiosity();
        $post->translations()->create(['locale' => 'ro', 'title' => 'Seulul, prima capitală', 'body' => 'Text în română.']);

        $this->withCookie('locale', 'ro')
            ->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.title', 'Seulul, prima capitală')
                ->where('post.contentLocale', 'ro')
                ->where('body', 'Text în română.')
            );
    }

    public function test_untranslated_posts_fall_back_to_english(): void
    {
        $post = $this->curiosity();

        $this->withCookie('locale', 'de')
            ->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.title', 'Seoul was the first capital')
                ->where('post.contentLocale', 'en')
            );
    }

    public function test_the_feed_shows_translated_titles(): void
    {
        $post = $this->curiosity();
        $post->translations()->create(['locale' => 'ko', 'title' => '서울은 첫 수도였다']);

        $this->withCookie('locale', 'ko')
            ->get(route('home', ['category' => 'curiosities']))
            ->assertInertia(fn (Assert $page) => $page->where('posts.0.title', '서울은 첫 수도였다'));
    }

    public function test_a_section_can_be_filtered_by_topic(): void
    {
        $history = $this->curiosity('Seoul was the first capital');
        $history->topics()->attach($this->topic('history'));
        $places = $this->curiosity('The Ryugyong Hotel');
        $places->topics()->attach($this->topic('places'));

        $this->get(route('home', ['category' => 'curiosities', 'topic' => 'history']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts', 1)
                ->where('posts.0.id', $history->id)
                ->where('activeTopic', 'history')
                // Only topics that have posts in the section become filters.
                ->has('topics', 2)
                ->where('topics.0.slug', 'history')
                ->where('topics.1.slug', 'places')
            );
    }

    public function test_an_unknown_topic_returns_404(): void
    {
        $this->get(route('home', ['category' => 'curiosities', 'topic' => 'nope']))->assertNotFound();
    }

    public function test_the_post_page_suggests_more_on_the_same_topic(): void
    {
        $first = $this->curiosity('Seoul was the first capital');
        $second = $this->curiosity('Kim Il Sung was born Kim Song Ju');
        $first->topics()->attach($this->topic('history'));
        $second->topics()->attach($this->topic('history'));

        $this->get(route('posts.show', $first))
            ->assertInertia(fn (Assert $page) => $page
                ->has('related', 1)
                ->where('related.0.id', $second->id)
                ->where('post.topics.0.slug', 'history')
            );
    }

    public function test_curiosities_can_be_imported_in_every_language_as_drafts(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'posts');
        file_put_contents($file, json_encode(['posts' => [[
            'key' => 'seoul-first-capital',
            'category' => 'curiosities',
            'topics' => ['history'],
            'original_url' => 'https://www.youtube.com/watch?v=E0bSV3Ck-QQ',
            'en' => ['title' => 'Seoul was the first capital', 'body' => 'English text.', 'editorial_note' => 'Sources: …'],
            'ro' => ['title' => 'Seulul, prima capitală', 'body' => 'Text în română.'],
        ]]]));

        $this->artisan('posts:import', ['file' => $file])
            ->expectsOutputToContain('1 new drafts, 0 updated')
            ->assertSuccessful();

        $post = Post::where('guid', 'nklens:seoul-first-capital')->firstOrFail();
        $this->assertNull($post->published_at);
        $this->assertNull($post->source_id);
        $this->assertSame(['history'], $post->topics->pluck('slug')->all());
        $this->assertSame('https://www.youtube.com/watch?v=E0bSV3Ck-QQ', $post->original_url);
        $this->assertSame('Seulul, prima capitală', $post->translationFor('ro')?->title);

        // Importing again updates the text and keeps a published post published.
        $post->publish();
        $this->artisan('posts:import', ['file' => $file])
            ->expectsOutputToContain('0 new drafts, 1 updated')
            ->assertSuccessful();
        $this->assertNotNull($post->fresh()?->published_at);
        $this->assertSame(1, $post->translations()->count());

        unlink($file);
    }
}
