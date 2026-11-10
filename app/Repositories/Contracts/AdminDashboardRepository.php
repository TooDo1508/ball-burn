<?php

namespace App\Repositories\Contracts;

use Carbon\CarbonImmutable;

interface AdminDashboardRepository
{
    /** @return array<string, mixed> */
    public function snapshot(
        CarbonImmutable $fromUtc,
        CarbonImmutable $toUtc,
        CarbonImmutable $previousFromUtc,
        CarbonImmutable $previousToUtc,
        CarbonImmutable $nowUtc,
    ): array;
}
