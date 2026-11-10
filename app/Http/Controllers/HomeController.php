<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Court;
use App\Repositories\Contracts\CourtRepository;
use App\Services\Blog\PublicBlogService;
use App\Services\Settings\ContactSettingsService;
use App\Services\Settings\HomePageService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(
        HomePageService $homePage,
        ContactSettingsService $contactSettings,
        CourtRepository $courts,
        PublicBlogService $blog,
    ): Response {
        $courtPage = $courts->paginateActive(4);
        $posts = $blog->listing(3)->getCollection();

        return Inertia::render('Home', [
            'content' => $homePage->content(),
            'contactInfo' => $contactSettings->publicSettings(),
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
            'posts' => $posts->map(fn (BlogPost $post): array => [
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'image' => $blog->imageUrl($post->cover_image_path),
                'imageAlt' => $post->cover_image_alt ?: $post->title,
                'category' => $post->categories->first()?->name,
                'publishedAt' => $post->published_at?->toAtomString(),
            ])->values(),
        ]);
    }
}
