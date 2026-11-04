<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'surface_type', 'court_type', 'capacity', 'price_per_hour', 'currency', 'is_active', 'sort_order'])]
#[Hidden(['created_at', 'updated_at'])]
class Court extends Model
{
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'price_per_hour' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(CourtImage::class)->orderBy('sort_order');
    }

    public function operatingHours(): HasMany
    {
        return $this->hasMany(CourtOperatingHour::class)->orderBy('day_of_week')->orderBy('opens_at');
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(CourtPriceRule::class)->orderBy('day_type')->orderBy('starts_at')->orderByDesc('effective_from');
    }

    public function closures(): HasMany
    {
        return $this->hasMany(CourtClosure::class)->orderBy('starts_at');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->orderBy('starts_at');
    }
}
