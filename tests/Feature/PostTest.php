<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SourceSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_from_state_media_are_propaganda_whatever_their_category(): void
    {
        $post = Post::factory()
            ->for(Source::factory()->stateMedia())
            ->create();

        $this->assertTrue($post->isPropaganda());
        $this->assertContains($post->id, Post::propaganda()->pluck('id'));
    }

    public function test_posts_in_the_propaganda_category_are_propaganda(): void
    {
        $post = Post::factory()
            ->for(Category::factory()->propaganda())
            ->create();

        $this->assertTrue($post->isPropaganda());
    }

    public function test_independent_news_is_not_propaganda(): void
    {
        $post = Post::factory()->create();

        $this->assertFalse($post->isPropaganda());
        $this->assertSame(0, Post::propaganda()->count());
    }

    public function test_the_feed_shows_only_published_posts_newest_first(): void
    {
        $older = Post::factory()->create(['published_at' => now()->subDays(2)]);
        $newer = Post::factory()->create(['published_at' => now()->subHour()]);
        Post::factory()->unpublished()->create();
        Post::factory()->create(['published_at' => now()->addDay()]);

        $this->assertSame([$newer->id, $older->id], Post::feed()->pluck('id')->all());
    }

    public function test_only_published_posts_are_searchable(): void
    {
        $published = Post::factory()->create();
        $draft = Post::factory()->unpublished()->create();
        $scheduled = Post::factory()->create(['published_at' => now()->addDay()]);

        $this->assertTrue($published->shouldBeSearchable());
        $this->assertFalse($draft->shouldBeSearchable());
        $this->assertFalse($scheduled->shouldBeSearchable());
    }

    public function test_searchable_fields_are_allowlisted_for_sources_that_forbid_ai(): void
    {
        $post = Post::factory()
            ->for(Source::factory()->state(['forbids_ai' => true]))
            ->create([
                'title' => 'Yonhap title',
                'excerpt' => 'Short excerpt',
                'body' => null,
                'editorial_note' => 'Human editorial context',
                'epigraph' => null,
                'language' => 'en',
            ]);

        $this->assertSame([
            'title' => 'Yonhap title',
            'excerpt' => 'Short excerpt',
            'body' => null,
            'editorial_note' => 'Human editorial context',
            'epigraph' => null,
            'language' => 'en',
        ], $post->toSearchableArray());
    }

    public function test_the_same_feed_item_cannot_be_imported_twice(): void
    {
        $source = Source::factory()->create();
        Post::factory()->for($source)->create(['guid' => 'item-1']);

        $this->expectException(UniqueConstraintViolationException::class);

        Post::factory()->for($source)->create(['guid' => 'item-1']);
    }

    public function test_the_mvp_categories_and_sources_are_seeded(): void
    {
        $this->seed([CategorySeeder::class, SourceSeeder::class]);

        $this->assertSame(9, Category::count());
        $this->assertTrue(Source::where('slug', 'rodong-sinmun')->first()->type->isPropaganda());
        // 38 North is seeded inactive, so NK News, Daily NK, KOREA NOW, Rodong Sinmun, the SCMP,
        // Charles North Korea and HaiHui în doi are fetched.
        $this->assertSame(7, Source::fetchable()->count());

        $charles = Source::where('slug', 'charles-north-korea')->firstOrFail();
        $this->assertSame('human-rights', $charles->importCategory()->slug);
        $this->assertTrue($charles->needs_review);
        $this->assertTrue($charles->skip_shorts);
        $this->assertNotNull($charles->youtubeChannelId());
        $this->assertSame('rodong', Source::where('slug', 'rodong-sinmun')->value('feed_format'));

        // A travel channel that covers many countries: only its North Korea videos, as drafts.
        $haihui = Source::where('slug', 'haihui-in-doi')->firstOrFail();
        $this->assertSame('travel-vlogs', $haihui->importCategory()->slug);
        $this->assertTrue($haihui->needs_review);
        $this->assertTrue($haihui->skip_shorts);
        $this->assertTrue($haihui->wants('O zi în Phenian'));
        $this->assertTrue($haihui->wants('Am intrat în Coreea de Nord'));
        $this->assertTrue($haihui->wants('Cum trăiesc nord-coreenii'));
        $this->assertFalse($haihui->wants('O săptămână în Japonia'));

        $koreaNow = Source::where('slug', 'korea-now')->firstOrFail();
        $this->assertSame(Category::KOREA_NOW, $koreaNow->category?->slug);
        $this->assertTrue($koreaNow->needs_review);
        $this->assertTrue($koreaNow->forbids_ai);
        $this->assertNotEmpty($koreaNow->keywords);

        // The SCMP covers all of Asia, so only North Korea stories are kept.
        $scmp = Source::where('slug', 'scmp')->firstOrFail();
        $this->assertTrue($scmp->wants('Kim Jong Un inspects a new missile'));
        $this->assertFalse($scmp->wants('Beijing urges peaceful unification with Taiwan'));
    }

    public function test_category_names_are_translated(): void
    {
        $category = Category::factory()->create(['slug' => 'news']);

        app()->setLocale('ro');

        $this->assertSame('Știri', $category->name());
    }
}
