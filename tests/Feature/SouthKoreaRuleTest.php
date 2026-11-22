<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Source;
use App\Support\VisitorRegion;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SouthKoreaRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
        config(['nklens.visitor_country' => null]);
    }

    private function rodongPost(): Post
    {
        $rodong = Source::factory()->stateMedia()->withoutFeed()->create([
            'name' => 'Rodong Sinmun',
            'website_url' => 'http://www.rodong.rep.kp',
        ]);

        return Post::factory()
            ->for($rodong)
            ->for(Category::where('slug', Category::PROPAGANDA)->firstOrFail())
            ->create([
                'title' => 'A new fish farm opens',
                'original_url' => 'http://www.rodong.rep.kp/en/index.php?article=1',
                'image_url' => 'http://www.rodong.rep.kp/photo/1.jpg',
            ]);
    }

    public function test_visitors_outside_south_korea_get_the_link_to_the_original(): void
    {
        $post = $this->rodongPost();

        $this->withHeader('CF-IPCountry', 'RO')
            ->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.originalUrl', 'http://www.rodong.rep.kp/en/index.php?article=1')
                ->where('post.linkWithheld', false)
            );
    }

    public function test_visitors_in_south_korea_get_no_links_to_north_korean_sites(): void
    {
        $post = $this->rodongPost();

        $this->withHeader('CF-IPCountry', 'KR')
            ->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.title', 'A new fish farm opens')
                ->where('post.originalUrl', null)
                ->where('post.imageUrl', null)
                ->where('post.linkWithheld', true)
                ->where('post.source.websiteUrl', null)
            )
            ->assertDontSee('rodong.rep.kp');
    }

    public function test_the_feed_follows_the_same_rule(): void
    {
        $this->rodongPost();

        $this->withHeader('CF-IPCountry', 'KR')
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('posts.0.originalUrl', null)
                ->where('posts.0.linkWithheld', true)
            )
            ->assertDontSee('rodong.rep.kp');
    }

    public function test_other_links_stay_for_visitors_in_south_korea(): void
    {
        $post = Post::factory()
            ->for(Category::where('slug', 'news')->firstOrFail())
            ->create(['original_url' => 'https://www.nknews.org/2026/11/a-story/']);

        $this->withHeader('CF-IPCountry', 'KR')
            ->get(route('posts.show', $post))
            ->assertInertia(fn (Assert $page) => $page
                ->where('post.originalUrl', 'https://www.nknews.org/2026/11/a-story/')
                ->where('post.linkWithheld', false)
            );
    }

    public function test_the_setting_stands_in_for_cloudflare_locally(): void
    {
        config(['nklens.visitor_country' => 'kr']);

        $this->assertTrue(VisitorRegion::fromRequest(Request::create('/'))->isSouthKorea());
    }

    public function test_every_kind_of_north_korean_address_is_caught(): void
    {
        $region = new VisitorRegion('KR');

        $this->assertFalse($region->allows('http://www.rodong.rep.kp/en/'));
        $this->assertFalse($region->allows('https://KCNA.KP/'));
        $this->assertFalse($region->allows('http://naenara.com.kp./'));
        $this->assertTrue($region->allows('https://www.kpmg.com/'));
        $this->assertTrue($region->allows('https://kcnawatch.org/'));
        $this->assertTrue($region->allows(null));
    }
}
