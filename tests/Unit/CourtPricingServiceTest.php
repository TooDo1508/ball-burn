<?php

namespace Tests\Unit;

use App\Models\Court;
use App\Models\CourtPriceRule;
use App\Services\Court\CourtPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class CourtPricingServiceTest extends TestCase
{
    public function test_batch_prices_preserve_tiers_latest_effective_rule_and_base_price_fallback(): void
    {
        $court = $this->court();
        $pricing = new CourtPricingService;
        $date = CarbonImmutable::parse('2026-11-09', 'Asia/Ho_Chi_Minh');
        $this->assertSame([
            '08:00' => '100000.00', '08:30' => '100000.00',
            '09:00' => '50000.00', '09:30' => '50000.00',
        ], $pricing->slotAmounts($court, $date, ['08:00', '08:30', '09:00', '09:30']));
        $this->assertSame('300000.00', $pricing->rangeAmount($court, $date->setTime(8, 0), $date->setTime(10, 0)));
    }

    public function test_range_recalculates_rules_when_crossing_into_a_weekend(): void
    {
        $court = $this->court();
        $court->priceRules->push(new CourtPriceRule([
            'day_type' => 'WEEKEND', 'starts_at' => '00:00:00', 'ends_at' => '23:59:59',
            'price_per_hour' => '300000.00', 'effective_from' => '2026-01-01', 'is_active' => true,
        ]));
        $start = CarbonImmutable::parse('2026-11-13 23:30:00', 'Asia/Ho_Chi_Minh');
        $this->assertSame('200000.00', (new CourtPricingService)->rangeAmount($court, $start, $start->addHour()));
    }

    private function court(): Court
    {
        $court = new Court(['price_per_hour' => '100000.00', 'currency' => 'VND']);
        $court->setRelation('priceRules', new Collection([
            new CourtPriceRule(['day_type' => 'WEEKDAY', 'starts_at' => '08:00:00', 'ends_at' => '09:00:00', 'price_per_hour' => '150000.00', 'effective_from' => '2026-01-01', 'is_active' => true]),
            new CourtPriceRule(['day_type' => 'WEEKDAY', 'starts_at' => '08:00:00', 'ends_at' => '09:00:00', 'price_per_hour' => '200000.00', 'effective_from' => '2026-11-01', 'is_active' => true]),
            new CourtPriceRule(['day_type' => 'WEEKDAY', 'starts_at' => '08:00:00', 'ends_at' => '09:00:00', 'price_per_hour' => '500000.00', 'effective_from' => '2026-12-01', 'is_active' => true]),
        ]));

        return $court;
    }
}
