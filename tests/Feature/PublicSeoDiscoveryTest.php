<?php

namespace Tests\Feature;

use Database\Seeders\NewsArticlesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Support\SeoUrl;
use Tests\TestCase;

class PublicSeoDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_public_pages_and_published_articles_only(): void
    {
        $this->seed(NewsArticlesSeeder::class);

        $response = $this->get('/sitemap.xml')->assertOk();
        $response->assertHeader('content-type', 'application/xml; charset=UTF-8');
        $response->assertSee('<loc>'.SeoUrl::to('/').'</loc>', false);
        $response->assertSee('<loc>'.SeoUrl::to('/about').'</loc>', false);
        $response->assertSee('/blog/luat-pickleball-co-ban-cho-nguoi-moi', false);
        $response->assertDontSee('/admin', false);
        $response->assertDontSee('/api/', false);
        $response->assertDontSee('/booking/status/', false);
    }

    public function test_llms_file_describes_the_venue_and_links_public_content(): void
    {
        $this->seed(NewsArticlesSeeder::class);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8')
            ->assertSee('# BALLBURN PickleClub & Bistro')
            ->assertSee('614 Lạc Long Quân')
            ->assertSee(SeoUrl::to('/booking'))
            ->assertSee('/blog/luat-pickleball-co-ban-cho-nguoi-moi');
    }

    public function test_homepage_has_canonical_metadata_in_initial_html(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('<link head-key="canonical" rel="canonical" href="'.SeoUrl::to('/').'">', false)
            ->assertSee('<meta head-key="description" name="description"', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_non_indexable_admin_status_and_json_endpoints_have_robots_header(): void
    {
        $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->get('/booking/status/'.str_repeat('a', 64))->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->getJson('/api/blog/posts')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
