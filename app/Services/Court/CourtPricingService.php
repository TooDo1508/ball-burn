<?php

namespace App\Services\Court;

use App\Models\Court;
use App\Models\CourtPriceRule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CourtPricingService
{
    public function applicableRules(Court $court, CarbonImmutable $date): Collection
    {
        $dayType = $date->isWeekend() ? 'WEEKEND' : 'WEEKDAY';

        return $court->priceRules
            ->where('day_type', $dayType)
            ->where('is_active', true)
            ->filter(fn (CourtPriceRule $rule): bool => $rule->effective_from->startOfDay()->lte($date->startOfDay())
                && (! $rule->effective_until || $rule->effective_until->endOfDay()->gte($date->endOfDay())))
            ->sortByDesc('effective_from')
            ->unique(fn (CourtPriceRule $rule): string => $rule->starts_at.'-'.$rule->ends_at)
            ->sortBy('starts_at')
            ->values();
    }

    public function slotAmount(Court $court, CarbonImmutable $date, string $startsAt): string
    {
        $rule = $this->applicableRules($court, $date)
            ->first(fn (CourtPriceRule $item): bool => $startsAt >= substr($item->starts_at, 0, 5)
                && $startsAt < substr($item->ends_at, 0, 5));
        $hourly = (string) ($rule?->price_per_hour ?? $court->price_per_hour);

        return $this->minorToDecimal(intdiv($this->decimalToMinor($hourly, $court->currency), 2), $court->currency);
    }

    public function rangeAmount(Court $court, CarbonImmutable $startsAt, CarbonImmutable $endsAt): string
    {
        $totalMinor = 0;
        $cursor = $startsAt;
        while ($cursor->lt($endsAt)) {
            $slotAmount = $this->slotAmount($court, $cursor->startOfDay(), $cursor->format('H:i'));
            $totalMinor += $this->decimalToMinor($slotAmount, $court->currency);
            $cursor = $cursor->addMinutes(30);
        }

        return $this->minorToDecimal($totalMinor, $court->currency);
    }

    private function decimalToMinor(string $amount, string $currency): int
    {
        $exponent = strtoupper($currency) === 'VND' ? 0 : 2;
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        if (! ctype_digit(ltrim($whole, '-')) || ($fraction !== '' && ! ctype_digit($fraction))) {
            throw ValidationException::withMessages(['price' => 'Giá sân không hợp lệ.']);
        }

        return ((int) $whole * (10 ** $exponent)) + (int) substr(str_pad($fraction, $exponent, '0'), 0, $exponent);
    }

    private function minorToDecimal(int $minor, string $currency): string
    {
        $exponent = strtoupper($currency) === 'VND' ? 0 : 2;
        if ($exponent === 0) {
            return $minor.'.00';
        }

        $factor = 10 ** $exponent;

        return intdiv($minor, $factor).'.'.str_pad((string) ($minor % $factor), $exponent, '0', STR_PAD_LEFT);
    }
}
