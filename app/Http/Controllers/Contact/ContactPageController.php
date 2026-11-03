<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Services\Settings\ContactSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class ContactPageController extends Controller
{
    public function __construct(private readonly ContactSettingsService $settings) {}

    public function indexPage(): View
    {
        $contact = $this->settings->publicSettings();
        $title = 'Liên hệ BALLBURN | PickleClub & Bistro';
        $description = 'Liên hệ BALLBURN để được hỗ trợ đặt sân, tìm hiểu lớp học, sự kiện và các dịch vụ tại Tây Hồ, Hà Nội.';

        return view('welcome', [
            'seo' => [
                'title' => $title,
                'description' => $description,
                'image' => url('/images/pickleball/court-rally-wide.jpg'),
                'canonical' => url('/contact'),
                'structured_data' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'ContactPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => url('/contact'),
                ],
            ],
            'initialContactSettings' => $contact,
        ]);
    }

    public function publicInfo(): JsonResponse
    {
        return response()->json(['data' => $this->settings->publicSettings()]);
    }
}
