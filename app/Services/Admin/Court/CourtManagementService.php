<?php

namespace App\Services\Admin\Court;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Repositories\Contracts\CourtRepository;
use App\Services\Court\CourtPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CourtManagementService
{
    public function __construct(
        private readonly CourtRepository $courts,
        private readonly CourtPricingService $pricing,
    ) {}

    public function schedule(string $date): array
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $dayStartLocal = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone)->startOfDay();
        $dayEndLocal = $dayStartLocal->addDay();
        $dayStartUtc = $dayStartLocal->utc();
        $dayEndUtc = $dayEndLocal->utc();
        $courts = $this->courts->allForAdmin();
        $bookings = $this->courts->bookingsBetween($dayStartUtc, $dayEndUtc);
        $closures = $this->courts->closuresBetween($dayStartUtc, $dayEndUtc);
        [$rangeStart, $rangeEnd] = $this->scheduleRange($courts, $dayStartLocal->dayOfWeekIso);
        $now = CarbonImmutable::now('UTC');
        $nowLocal = $now->timezone($timezone);

        return [
            'date' => $date,
            'today' => $nowLocal->format('Y-m-d'),
            'now_time' => $nowLocal->format('H:i'),
            'date_label' => $dayStartLocal->locale('vi')->isoFormat('dddd, DD/MM/YYYY'),
            'timezone' => $timezone,
            'range' => ['starts_at' => $rangeStart, 'ends_at' => $rangeEnd],
            'time_slots' => $this->timeSlots($rangeStart, $rangeEnd),
            'courts' => $courts->map(fn (Court $court): array => $this->courtPayload($court, $dayStartLocal))->values()->all(),
            'bookings' => $bookings->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'court_id' => $booking->court_id,
                'customer_name' => $booking->customer_name,
                'booking_code' => $booking->booking_code,
                'status' => $booking->status,
                'starts_at' => $booking->starts_at->timezone($timezone)->format('H:i'),
                'ends_at' => $booking->ends_at->timezone($timezone)->format('H:i'),
                'is_in_use' => $booking->starts_at->lte($now) && $booking->ends_at->gt($now),
            ])->values()->all(),
            'closures' => $closures->map(fn (CourtClosure $closure): array => [
                'id' => $closure->id,
                'court_id' => $closure->court_id,
                'reason' => $closure->reason,
                'starts_at' => $closure->starts_at->timezone($timezone)->format('H:i'),
                'ends_at' => $closure->ends_at->timezone($timezone)->format('H:i'),
            ])->values()->all(),
            'stats' => $this->stats($courts, $bookings, $closures, $dayStartLocal->dayOfWeekIso, $now),
        ];
    }

    public function find(int $courtId): Court
    {
        return $this->courts->findForAdmin($courtId);
    }

    public function create(array $validated, ?UploadedFile $image): Court
    {
        return $this->save(new Court, $validated, $image);
    }

    public function update(Court $court, array $validated, ?UploadedFile $image): Court
    {
        return $this->save($court, $validated, $image);
    }

    public function createClosure(array $validated, int $userId): CourtClosure
    {
        $timezone = (string) config('venue.timezone', 'Asia/Ho_Chi_Minh');
        $startsAtLocal = CarbonImmutable::createFromFormat('Y-m-d H:i', $validated['date'].' '.$validated['starts_at'], $timezone);
        $endsAtLocal = CarbonImmutable::createFromFormat('Y-m-d H:i', $validated['date'].' '.$validated['ends_at'], $timezone);
        $court = $this->courts->findForAdmin((int) $validated['court_id']);
        $hours = $court->operatingHours->firstWhere('day_of_week', $startsAtLocal->dayOfWeekIso);

        if (! $hours || $hours->is_closed || $startsAtLocal->format('H:i') < substr($hours->opens_at, 0, 5) || $endsAtLocal->format('H:i') > substr($hours->closes_at, 0, 5)) {
            throw ValidationException::withMessages([
                'starts_at' => 'Khoảng khóa phải nằm trong giờ hoạt động của sân.',
            ]);
        }

        return $this->courts->createClosure(
            $court->id,
            $startsAtLocal->utc(),
            $endsAtLocal->utc(),
            $validated['reason'] ?? null,
            $userId,
        );
    }

    public function deleteClosure(int $closureId): void
    {
        $this->courts->deleteClosure($closureId);
    }

    private function save(Court $court, array $validated, ?UploadedFile $image): Court
    {
        $manualSlug = trim((string) ($validated['slug'] ?? ''));
        $slug = Str::slug($manualSlug ?: $validated['name']) ?: 'san-pickleball';
        if ($this->courts->slugExists($slug, $court->exists ? $court->id : null)) {
            if ($manualSlug !== '') {
                throw ValidationException::withMessages(['slug' => 'Đường dẫn này đã được sử dụng.']);
            }

            $base = $slug;
            $suffix = 2;
            while ($this->courts->slugExists($slug, $court->exists ? $court->id : null)) {
                $slug = $base.'-'.$suffix++;
            }
        }

        $newImagePath = $image?->store('courts', 'public');
        $oldPrimaryImage = $court->images->firstWhere('is_primary', true)?->image_path;
        $imageAttributes = $newImagePath ? [
            'image_path' => $newImagePath,
            'alt_text' => $validated['name'],
            'sort_order' => 0,
            'is_primary' => true,
        ] : null;

        $attributes = [
            'name' => trim($validated['name']),
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'surface_type' => $validated['surface_type'] ?? null,
            'court_type' => $validated['court_type'],
            'capacity' => $validated['capacity'] ?? null,
            'price_per_hour' => $validated['price_per_hour'],
            'currency' => strtoupper($validated['currency']),
            'is_active' => (bool) $validated['is_active'],
            'sort_order' => (int) $validated['sort_order'],
        ];
        $hours = collect($validated['operating_hours'])->map(fn (array $hour): array => [
            'day_of_week' => (int) $hour['day_of_week'],
            'opens_at' => $hour['is_closed'] ? '00:00:00' : $hour['opens_at'].':00',
            'closes_at' => $hour['is_closed'] ? '00:00:00' : $hour['closes_at'].':00',
            'is_closed' => (bool) $hour['is_closed'],
        ])->all();
        $priceRules = collect($validated['price_rules'] ?? [])->map(fn (array $rule): array => [
            'day_type' => $rule['day_type'],
            'starts_at' => $rule['starts_at'].':00',
            'ends_at' => $rule['ends_at'].':00',
            'price_per_hour' => $rule['price_per_hour'],
            'currency' => strtoupper($validated['currency']),
            'effective_from' => $rule['effective_from'],
            'effective_until' => null,
            'is_active' => true,
        ])->all();

        try {
            $saved = $this->courts->saveForAdmin($court, $attributes, $hours, $priceRules, $imageAttributes);
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        if ($newImagePath && $this->isStoredPath($oldPrimaryImage)) {
            Storage::disk('public')->delete($oldPrimaryImage);
        }

        return $saved;
    }

    private function courtPayload(Court $court, CarbonImmutable $date): array
    {
        $dayOfWeek = $date->dayOfWeekIso;
        $hour = $court->operatingHours->firstWhere('day_of_week', $dayOfWeek);
        $primaryImage = $court->images->firstWhere('is_primary', true) ?? $court->images->first();
        $priceRules = $this->pricing->applicableRules($court, $date);
        $editablePriceRules = $court->priceRules
            ->where('is_active', true)
            ->filter(fn ($rule): bool => $rule->effective_from->startOfDay()->lte($date->startOfDay())
                && (! $rule->effective_until || $rule->effective_until->endOfDay()->gte($date->endOfDay())))
            ->sortByDesc('effective_from')
            ->unique(fn ($rule): string => $rule->day_type.'-'.$rule->starts_at.'-'.$rule->ends_at)
            ->sortBy(fn ($rule): string => $rule->day_type.'-'.$rule->starts_at)
            ->values();
        $minimumSlotPrice = $priceRules->isNotEmpty()
            ? (string) $priceRules
                ->map(fn ($rule): int => (int) $this->pricing->slotAmount($court, $date, substr($rule->starts_at, 0, 5)))
                ->min().'.00'
            : $this->pricing->slotAmount($court, $date, '05:00');

        return [
            'id' => $court->id,
            'name' => $court->name,
            'slug' => $court->slug,
            'description' => $court->description,
            'surface_type' => $court->surface_type,
            'court_type' => $court->court_type,
            'capacity' => $court->capacity,
            'price_per_hour' => $court->price_per_hour,
            'price_per_slot' => $minimumSlotPrice,
            'currency' => $court->currency,
            'is_active' => $court->is_active,
            'sort_order' => $court->sort_order,
            'image_url' => $primaryImage ? $this->imageUrl($primaryImage->image_path) : null,
            'day_hours' => $hour ? [
                'is_closed' => $hour->is_closed,
                'opens_at' => substr($hour->opens_at, 0, 5),
                'closes_at' => substr($hour->closes_at, 0, 5),
            ] : null,
            'operating_hours' => $court->operatingHours->map(fn ($item): array => [
                'day_of_week' => $item->day_of_week,
                'is_closed' => $item->is_closed,
                'opens_at' => substr($item->opens_at, 0, 5),
                'closes_at' => substr($item->closes_at, 0, 5),
            ])->values()->all(),
            'price_rules' => $editablePriceRules->map(fn ($rule): array => [
                'day_type' => $rule->day_type,
                'starts_at' => substr($rule->starts_at, 0, 5),
                'ends_at' => substr($rule->ends_at, 0, 5),
                'price_per_hour' => $rule->price_per_hour,
                'effective_from' => $rule->effective_from->format('Y-m-d'),
            ])->values()->all(),
        ];
    }

    private function scheduleRange(Collection $courts, int $dayOfWeek): array
    {
        $hours = $courts->flatMap(fn (Court $court) => $court->operatingHours
            ->where('day_of_week', $dayOfWeek)
            ->where('is_closed', false));

        if ($hours->isEmpty()) {
            return ['08:00', '20:00'];
        }

        return [
            substr((string) $hours->min('opens_at'), 0, 5),
            substr((string) $hours->max('closes_at'), 0, 5),
        ];
    }

    private function timeSlots(string $startsAt, string $endsAt): array
    {
        $slots = [];
        $cursor = CarbonImmutable::createFromFormat('H:i', $startsAt);
        $end = CarbonImmutable::createFromFormat('H:i', $endsAt);
        while ($cursor->lt($end)) {
            $slotEnd = $cursor->addMinutes(30);
            $slots[] = ['starts_at' => $cursor->format('H:i'), 'ends_at' => $slotEnd->format('H:i')];
            $cursor = $slotEnd;
        }

        return $slots;
    }

    private function stats(Collection $courts, Collection $bookings, Collection $closures, int $dayOfWeek, CarbonImmutable $now): array
    {
        $totalSlots = $courts->sum(function (Court $court) use ($dayOfWeek): int {
            $hour = $court->operatingHours->firstWhere('day_of_week', $dayOfWeek);
            if (! $hour || $hour->is_closed) {
                return 0;
            }

            return intdiv($this->minutesBetween(substr($hour->opens_at, 0, 5), substr($hour->closes_at, 0, 5)), 30);
        });
        $bookedSlots = $bookings->sum(fn (Booking $booking): int => max(1, (int) ceil($booking->starts_at->diffInMinutes($booking->ends_at) / 30)));
        $blockedSlots = $closures->sum(fn (CourtClosure $closure): int => max(1, (int) ceil($closure->starts_at->diffInMinutes($closure->ends_at) / 30)));

        return [
            'booked' => $bookings->count(),
            'in_use' => $bookings->filter(fn (Booking $booking): bool => $booking->starts_at->lte($now) && $booking->ends_at->gt($now))->count(),
            'available_slots' => max(0, $totalSlots - $bookedSlots - $blockedSlots),
            'total_slots' => $totalSlots,
            'blocked_slots' => $blockedSlots,
        ];
    }

    private function minutesBetween(string $start, string $end): int
    {
        return CarbonImmutable::createFromFormat('H:i', $start)->diffInMinutes(CarbonImmutable::createFromFormat('H:i', $end));
    }

    private function imageUrl(string $path): string
    {
        return preg_match('~^https?://~i', $path) || str_starts_with($path, '/') ? $path : '/storage/'.$path;
    }

    private function isStoredPath(?string $path): bool
    {
        return filled($path) && ! preg_match('~^https?://~i', $path) && ! str_starts_with($path, '/');
    }
}
