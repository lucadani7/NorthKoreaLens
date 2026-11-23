<?php

namespace Tests\Feature;

use App\Feeds\RodongParser;
use App\Jobs\FetchSourceFeed;
use App\Models\Category;
use App\Models\Source;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RodongSinmunTest extends TestCase
{
    use RefreshDatabase;

    /** Cut from the real English home page of rodong.rep.kp on 22 November 2026. */
    private const HOME_PAGE = <<<'HTML'
        <div class="row"><a href="index.php?MUBAMkAxQA==" class="mu1">News</a></div>
        <div id="revoListDIV"><ul><li><a href="index.php?MTJAMjAyNi0xMS0yMS0wMTNAMUAxQEAwQDFA==" target="_blank">Respected Comrade <nobr><b>Kim Jong Un</b></nobr> Inspects School Fixtures Factory and Educational Apparatus Factory under Construction <i class="fa fa-camera"></i></a><nobr><div class="revo_date"><i class="fa fa-calendar-o"></i> 2026.11.21.</div></nobr></li></ul></div>
        <div id="newsDIV">
        <div class="media">
          <div class="media-body">
            <h4 class="media-heading">
        <a href="index.php?MTJAMjAyNi0xMS0yMi0wMDFAMkAxQEAwQDFA==" target="_blank">
        On Road of Endless Devotion to People &nbsp;<span><img src="images/newsf.gif" alt=""></span></a></h4>
          </div>
        </div>
        <div class="media">
          <div class="media-left">
         <a href="index.php?MTJAMjAyNi0xMS0yMi0wMDRAMkAxQEAwQDRA==" target="_blank"><img src="data:;base64,/9j/4AAQ" style="width:100%;" alt=""></a>
          </div>
          <div class="media-body">
            <h4 class="media-heading">
        <a href="index.php?MTJAMjAyNi0xMS0yMi0wMDRAMkAxQEAwQDRA==" target="_blank">
        New Houses Built at Taedonggang Combined Fruit Farm &nbsp;<span><img src="images/newsf.gif" alt=""></span></a></h4>
          </div>
        </div>
        </div>
        HTML;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
    }

    private function rodong(): Source
    {
        return Source::factory()->stateMedia()->create([
            'name' => 'Rodong Sinmun',
            'slug' => 'rodong-sinmun',
            'website_url' => 'http://www.rodong.rep.kp',
            'feed_url' => 'http://www.rodong.rep.kp/en/',
            'feed_format' => 'rodong',
            'fetch_every_hours' => 24,
            'needs_review' => true,
        ]);
    }

    public function test_it_reads_the_headlines_of_the_home_page(): void
    {
        $items = (new RodongParser)->parse(self::HOME_PAGE, 'http://www.rodong.rep.kp/en/');

        // Menu links are not articles, and an article linked from its photo and its title counts once.
        $this->assertCount(3, $items);

        $this->assertSame('rodong:2026-11-21-013', $items[0]->guid);
        $this->assertSame('Respected Comrade Kim Jong Un Inspects School Fixtures Factory and Educational Apparatus Factory under Construction', $items[0]->title);
        $this->assertSame('http://www.rodong.rep.kp/en/index.php?MTJAMjAyNi0xMS0yMS0wMTNAMUAxQEAwQDFA==', $items[0]->link);
        $this->assertSame('2026-11-21', $items[0]->publishedAt?->format('Y-m-d'));

        $this->assertSame('On Road of Endless Devotion to People', $items[1]->title);
        $this->assertSame('New Houses Built at Taedonggang Combined Fruit Farm', $items[2]->title);
        $this->assertNull($items[2]->imageUrl);
        $this->assertNull($items[2]->excerpt);
    }

    public function test_headlines_become_propaganda_drafts(): void
    {
        Http::fake(['www.rodong.rep.kp/*' => Http::response(self::HOME_PAGE)]);
        $source = $this->rodong();

        FetchSourceFeed::dispatchSync($source);

        $this->assertSame(3, $source->posts()->count());

        foreach ($source->posts as $post) {
            $this->assertSame(Category::PROPAGANDA, $post->category->slug);
            $this->assertTrue($post->isPropaganda());
            $this->assertNull($post->published_at);
        }
    }

    public function test_the_hourly_run_reads_it_once_a_day(): void
    {
        Queue::fake();
        $source = $this->rodong();

        $source->forceFill(['last_fetched_at' => now()->subHours(3)])->save();
        $this->artisan('feeds:fetch')->expectsOutputToContain('Nothing is due')->assertSuccessful();
        Queue::assertNothingPushed();

        $source->forceFill(['last_fetched_at' => now()->subHours(24)])->save();
        $this->artisan('feeds:fetch')->assertSuccessful();
        Queue::assertPushed(FetchSourceFeed::class, 1);
    }

    public function test_naming_the_source_fetches_it_at_once(): void
    {
        Queue::fake();
        $this->rodong()->forceFill(['last_fetched_at' => now()->subHour()])->save();

        $this->artisan('feeds:fetch', ['source' => 'rodong-sinmun'])->assertSuccessful();

        Queue::assertPushed(FetchSourceFeed::class, 1);
    }
}
