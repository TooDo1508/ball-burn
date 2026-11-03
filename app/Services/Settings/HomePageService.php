<?php

namespace App\Services\Settings;

use App\Repositories\Contracts\SiteSettingRepository;

class HomePageService
{
    public function __construct(private readonly SiteSettingRepository $settings) {}

    public function content(): array
    {
        $content = array_merge(
            self::defaults(),
            $this->settings->findByKey('home_page')?->setting_value ?? [],
        );

        foreach (['hero_image', 'og_image'] as $imageKey) {
            if (is_string($content[$imageKey] ?? null) && str_contains($content[$imageKey], 'images.unsplash.com')) {
                $content[$imageKey] = self::defaults()[$imageKey];
            }
        }

        if (is_string($content['og_image'] ?? null) && str_starts_with($content['og_image'], '/')) {
            $content['og_image'] = url($content['og_image']);
        }

        return $content;
    }

    public function update(array $content, int $updatedBy): void
    {
        $this->settings->updateOrCreate('home_page', [
            'setting_value' => $content,
            'description' => 'Public home page content and SEO metadata',
            'updated_by' => $updatedBy,
        ]);
    }

    public static function defaults(): array
    {
        return [
            'site_name' => 'BALLBURN PickleClub & Bistro',
            'meta_title' => 'BALLBURN PickleClub & Bistro | Sân chơi của bạn',
            'meta_description' => 'Tám sân pickleball, VAR, bistro và cộng đồng chơi vui mỗi ngày tại BALLBURN Tây Hồ, Hà Nội.',
            'hero_title' => 'Chơi hết mình.\nVui hết cỡ.',
            'hero_description' => 'Tám sân pickleball có mái che, bistro ngay bên sân và những cuộc hẹn dễ kéo dài hơn dự tính. BALLBURN là điểm đến mới cho một ngày vận động trọn vẹn tại Hồ Tây.',
            'hero_image' => '/images/pickleball/court-rally-wide.jpg',
            'about_title' => 'Tám sân. 2.700 m². Một điểm hẹn.',
            'about_text' => 'Từ cú chạm vợt đầu tiên đến bữa ăn sau trận, BALLBURN kết nối thể thao, ẩm thực và cộng đồng trong một không gian tại Tây Hồ. Người mới có thể thuê vợt, làm quen với pickleball; người chơi lâu năm có sân, lớp học và giải giao lưu để tiếp tục tiến bộ.',
            'courts_title' => 'Tám sân, vô vàn pha bóng.',
            'courts_text' => 'Sân có mái che và hệ thống làm mát hiện đại để bạn tận hưởng trận đấu quanh năm. Với hệ thống VAR, những pha bóng đáng nhớ có thể được xem lại ngay tại sân.',
            'bistro_title' => 'Chơi vui. Ăn ngon. Gắn kết.',
            'bistro_text' => 'Sân chơi chỉ là màn mở đầu. Sau trận, mời cả nhóm ghé bistro và lounge để dùng bữa, gọi món yêu thích, thư giãn giữa những trận đấu và kể tiếp câu chuyện của mình.',
            'booking_title' => 'Hẹn bạn trên sân nhé.',
            'og_image' => '/images/pickleball/court-rally-wide.jpg',
        ];
    }
}
