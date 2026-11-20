<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Services\Court\CourtService;
use App\Services\Blog\PublicBlogService;
use App\Services\Settings\ContactSettingsService;
use App\Services\Settings\HomePageService;
use App\Support\SeoUrl;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(
        HomePageService $homePage,
        ContactSettingsService $contactSettings,
        CourtService $courts,
        PublicBlogService $blog,
    ): Response {
        $content = $homePage->content();
        $contact = $contactSettings->publicSettings();
        $canonical = SeoUrl::to('/');
        $addressParts = explode(',', (string) ($contact['address'] ?? '614 Lạc Long Quân'));
        $coordinates = array_map('trim', explode(',', (string) ($contact['map_coordinates'] ?? '')));
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'SportsActivityLocation',
            '@id' => $canonical.'#venue',
            'name' => $content['site_name'],
            'description' => $content['meta_description'],
            'image' => $content['og_image'],
            'url' => $canonical,
            'telephone' => $contact['phone'] ?? null,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => trim($addressParts[0]),
                'addressLocality' => 'Tây Hồ',
                'addressRegion' => 'Hà Nội',
                'postalCode' => '100000',
                'addressCountry' => 'VN',
            ],
            'sameAs' => array_values(array_filter([
                $contact['facebook_url'] ?? null,
                $contact['instagram_url'] ?? null,
            ])),
        ];

        if (count($coordinates) === 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
            $structuredData['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $coordinates[0],
                'longitude' => (float) $coordinates[1],
            ];
        }

        $courtPage = $courts->paginateActive(4);
        $posts = $blog->listing(3)->getCollection();

        return Inertia::render('Home', [
            'content' => $content,
            'contactInfo' => $contact,
            'seo' => [
                'title' => $content['meta_title'],
                'description' => $content['meta_description'],
                'canonical' => $canonical,
                'image' => $content['og_image'],
                'structured_data' => $structuredData,
            ],
            'courtCount' => $courtPage->total(),
            'courts' => $courtPage->getCollection()->map(fn (Court $court): array => [
                'name' => $court->name,
                'type' => $court->court_type === 'CENTER' ? 'Sân trung tâm' : 'Sân tiêu chuẩn',
                'description' => $court->description ?: 'Sẵn sàng cho trận đấu tiếp theo của bạn.',
                'surface' => $court->surface_type ?: 'Mái che · Mặt acrylic',
                'image' => $court->images->firstWhere('is_primary', true)?->image_path
                    ?? $court->images->first()?->image_path
                    ?? '/images/pickleball/court-rally-wide.jpg',
                'imageAlt' => $court->images->firstWhere('is_primary', true)?->alt_text
                    ?? $court->name.' tại BALLBURN PickleClub & Bistro',
            ])->values(),
            'posts' => $posts->map(fn (array $post): array => [
                'title' => $post['title'],
                'slug' => $post['slug'],
                'excerpt' => $post['excerpt'],
                'image' => $blog->imageUrl($post['cover_image_path']),
                'imageAlt' => $post['cover_image_alt'] ?: $post['title'],
                'category' => $post['categories'][0] ?? null,
                'publishedAt' => $post['published_at'],
            ])->values(),
        ]);
    }
}
