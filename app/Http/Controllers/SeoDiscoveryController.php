<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\BlogPostRepository;
use App\Services\Settings\ContactSettingsService;
use App\Support\SeoUrl;
use Illuminate\Http\Response;

class SeoDiscoveryController extends Controller
{
    public function __construct(private readonly BlogPostRepository $posts) {}

    public function sitemap(): Response
    {
        $urls = collect(['/', '/about', '/blog', '/booking', '/contact'])
            ->map(fn (string $path): array => ['loc' => SeoUrl::to($path), 'lastmod' => null]);

        foreach ($this->posts->publishedForSitemap() as $post) {
            $urls->push([
                'loc' => SeoUrl::to('/blog/'.$post->slug),
                'lastmod' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
            ]);
        }

        return response()->view('seo.sitemap', ['urls' => $urls], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300, s-maxage=300',
        ]);
    }

    public function llms(ContactSettingsService $contact): Response
    {
        $venue = $contact->publicSettings();
        $posts = $this->posts->publishedForSitemap();
        $base = rtrim(SeoUrl::to('/'), '/');
        $lines = [
            '# BALLBURN PickleClub & Bistro',
            '',
            '> BALLBURN is a pickleball club and bistro at '.($venue['address'] ?? '614 Lạc Long Quân, Tây Hồ, Hà Nội, Vietnam').'.',
            '',
            '## Official pages',
            '',
            '- [Home]('.$base.'/): venue overview, courts, bistro, and community.',
            '- [About]('.$base.'/about): information about BALLBURN.',
            '- [Booking]('.$base.'/booking): view live court availability and book a court.',
            '- [Blog]('.$base.'/blog): Vietnamese pickleball guides and news.',
            '- [Contact]('.$base.'/contact): contact details and directions.',
            '- [XML sitemap]('.$base.'/sitemap.xml): canonical public URLs.',
            '',
            '## Venue information',
            '',
            '- Address: '.($venue['address'] ?? '614 Lạc Long Quân, Tây Hồ, Hà Nội 100000'),
            '- Phone: '.($venue['phone'] ?? '0886 688 614'),
            '- Business hours: '.($venue['business_hours'] ?? 'Mỗi ngày · 05:00–24:00'),
            '- Website: '.$base.'/',
            '',
            '## Published articles',
            '',
        ];

        foreach ($posts as $post) {
            $title = str_replace(["\r", "\n", '[', ']'], '', $post->title);
            $lines[] = '- ['.$title.']('.$base.'/blog/'.$post->slug.')'.($post->excerpt ? ': '.str_replace(["\r", "\n"], ' ', $post->excerpt) : '');
        }

        $lines[] = '';

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300, s-maxage=300',
        ]);
    }
}
