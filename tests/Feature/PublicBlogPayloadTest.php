<?php

namespace Tests\Feature;

use Database\Seeders\NewsArticlesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBlogPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_pages_and_json_api_share_public_article_fields(): void
    {
        $this->withoutVite();
        $this->seed(NewsArticlesSeeder::class);
        $list = $this->getJson('/api/blog/posts')->assertOk()->json('data');
        $this->assertNotEmpty($list);
        $summaries = array_map(function (array $post): array {
            unset($post['url']);

            return $post;
        }, $list);
        $this->get('/blog')->assertOk()->assertViewHas('initialBlogPosts', fn ($value) => json_decode(json_encode($value), true) === $summaries);

        $article = $this->getJson('/api/blog/posts/'.$list[0]['slug'])->assertOk()->json('data');
        unset($article['updated_at']);
        $this->get('/blog/'.$list[0]['slug'])->assertOk()->assertViewHas('initialBlogPost', fn ($value) => json_decode(json_encode($value), true) === $article);
    }
}
