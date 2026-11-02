<?php

namespace App\Services\Settings;

use App\Repositories\Contracts\SiteSettingRepository;

class HomePageService
{
    public function __construct(private readonly SiteSettingRepository $settings) {}

    public function content(): array
    {
        return $this->settings->findByKey('home_page')?->setting_value ?? self::defaults();
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
            'meta_description' => 'Sân pickleball, cộng đồng năng động và không gian bistro cho những cuộc gặp gỡ đáng nhớ tại BALLBURN.',
            'hero_title' => 'Chơi hết mình. Vui hết cỡ.',
            'hero_description' => 'Một điểm hẹn mới cho những trận pickleball đầy năng lượng, những cuộc gặp gỡ thân tình và phút thư giãn ngon lành.',
            'hero_image' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1500&q=85',
            'about_title' => 'Không chỉ là một sân chơi. Là điểm hẹn.',
            'about_text' => 'BALLBURN mang pickleball đến gần hơn với mọi người — từ người mới cầm vợt đến những tay chơi đầy đam mê. Đến để vận động, ở lại để kết nối.',
            'courts_title' => 'Mỗi trận đấu, một câu chuyện.',
            'courts_text' => 'Không gian chỉn chu, mặt sân chất lượng và bầu không khí khiến bạn muốn chơi thêm một set nữa.',
            'bistro_title' => 'Chơi vui. Ăn ngon. Gắn kết.',
            'bistro_text' => 'Trận đấu kết thúc, cuộc vui vẫn tiếp tục. Thư giãn cùng bạn bè với những món ngon và thức uống yêu thích.',
            'booking_title' => 'Hẹn bạn trên sân nhé.',
            'og_image' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=85',
        ];
    }
}
