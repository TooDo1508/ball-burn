<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourtCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $courts = [
            ['name' => 'Sân 1', 'type' => 'CENTER', 'description' => 'Sân trung tâm dành cho trận đấu nổi bật, giải giao lưu và sự kiện câu lạc bộ; góc nhìn rộng, gần khu lounge và hệ thống VAR.'],
            ['name' => 'Sân 2', 'type' => 'CENTER', 'description' => 'Sân trung tâm tiêu chuẩn thi đấu với mặt acrylic êm chân, ánh sáng đồng đều và khu vực chờ thuận tiện cho đội nhóm.'],
            ['name' => 'Sân 3', 'type' => 'CENTER', 'description' => 'Sân trung tâm có vị trí đẹp trong cụm sân, phù hợp tổ chức lớp nâng cao, trận đấu ghi hình và hoạt động cộng đồng.'],
            ['name' => 'Sân 4', 'type' => 'REGULAR', 'description' => 'Sân thường thân thiện cho người mới, nhóm bạn và các buổi tập kỹ thuật; có mái che và hệ thống làm mát.'],
            ['name' => 'Sân 5', 'type' => 'REGULAR', 'description' => 'Sân thường dành cho lịch chơi hằng tuần, mặt sân bám tốt và khoảng trống an toàn quanh đường biên.'],
            ['name' => 'Sân 6', 'type' => 'REGULAR', 'description' => 'Sân thường phù hợp lớp học, luyện đôi và nhóm nhỏ; thuận tiện tiếp cận khu thay đồ và quầy dịch vụ.'],
            ['name' => 'Sân 7', 'type' => 'REGULAR', 'description' => 'Sân thường có không gian thoáng, phù hợp gia đình, hội bạn và những buổi giao lưu pickleball cuối tuần.'],
            ['name' => 'Sân 8', 'type' => 'REGULAR', 'description' => 'Sân thường nằm gần khu nghỉ, thích hợp cho trận vui, hoạt động cộng đồng và khách mới trải nghiệm BALLBURN.'],
        ];
        $images = [
            '/images/pickleball/court-rally-wide.jpg',
            '/images/pickleball/court-community.jpg',
            '/images/pickleball/player-action.jpg',
            '/images/pickleball/paddle-court.jpg',
        ];

        DB::transaction(function () use ($courts, $images): void {
            foreach ($courts as $index => $item) {
                $number = $index + 1;
                $isCenter = $item['type'] === 'CENTER';
                $court = Court::query()->updateOrCreate(
                    ['slug' => "san-{$number}"],
                    [
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'surface_type' => 'Mái che · Mặt acrylic',
                        'court_type' => $item['type'],
                        'capacity' => 4,
                        'price_per_hour' => $isCenter ? 250000 : 140000,
                        'currency' => 'VND',
                        'is_active' => true,
                        'sort_order' => $number,
                    ],
                );

                $court->operatingHours()->delete();
                $court->operatingHours()->createMany(collect(range(1, 7))->map(fn (int $day): array => [
                    'day_of_week' => $day,
                    'opens_at' => '05:00:00',
                    'closes_at' => '23:00:00',
                    'is_closed' => false,
                ])->all());

                $court->images()->where('is_primary', true)->delete();
                $court->images()->create([
                    'image_path' => $images[$index % count($images)],
                    'alt_text' => $item['name'].' tại BALLBURN PickleClub & Bistro',
                    'sort_order' => 0,
                    'is_primary' => true,
                ]);

                $court->priceRules()->delete();
                $court->priceRules()->createMany($this->priceRules($item['type']));
            }
        });
    }

    private function priceRules(string $courtType): array
    {
        $times = [['05:00', '08:00'], ['08:00', '12:00'], ['12:00', '14:00'], ['14:00', '16:00'], ['16:00', '23:00']];
        $prices = $courtType === 'CENTER'
            ? ['WEEKDAY' => [250000, 250000, 250000, 300000, 400000], 'WEEKEND' => [300000, 350000, 300000, 350000, 400000]]
            : ['WEEKDAY' => [140000, 190000, 140000, 220000, 299000], 'WEEKEND' => [220000, 250000, 250000, 270000, 299000]];

        $rules = [];
        foreach ($prices as $dayType => $amounts) {
            foreach ($times as $index => [$startsAt, $endsAt]) {
                $rules[] = [
                    'day_type' => $dayType,
                    'starts_at' => $startsAt.':00',
                    'ends_at' => $endsAt.':00',
                    'price_per_hour' => $amounts[$index],
                    'currency' => 'VND',
                    'effective_from' => '2026-04-01',
                    'effective_until' => null,
                    'is_active' => true,
                ];
            }
        }

        return $rules;
    }
}
