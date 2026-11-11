<?php

namespace App\Services\Court;

use App\Models\Court;
use App\Models\CourtImage;
use App\Repositories\Contracts\CourtRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorResult;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

class CourtService
{
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly CourtRepository $courts,
        private readonly PublicCourtCatalogCache $catalogCache,
    ) {}

    public function paginateActive(int $perPage = 12): LengthAwarePaginator
    {
        $version = $this->catalogCache->version();
        $page = Paginator::resolveCurrentPage();

        $payload = Cache::remember(
            "public-courts:list:{$version}:{$perPage}:{$page}",
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => $this->catalogPayload($perPage),
        );

        $items = collect($payload['items'])->map(function (array $item): Court {
            $court = (new Court)->newFromBuilder($item['attributes']);
            $images = collect($item['images'])->map(fn (array $attributes): CourtImage => (new CourtImage)->newFromBuilder($attributes));
            $court->setRelation('images', new EloquentCollection($images->all()));

            return $court;
        });

        return new PaginatorResult($items, $payload['total'], $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }

    public function findActiveBySlug(string $slug): ?Court
    {
        return $this->courts->findActiveBySlug($slug);
    }

    /** @return array{items: array<int, array{attributes: array<string, mixed>, images: array<int, array<string, mixed>>}>, total: int} */
    private function catalogPayload(int $perPage): array
    {
        $page = $this->courts->paginateActive($perPage);

        return [
            'items' => $page->getCollection()->map(fn (Court $court): array => [
                'attributes' => $court->getAttributes(),
                'images' => $court->images->map(fn (CourtImage $image): array => $image->getAttributes())->all(),
            ])->all(),
            'total' => $page->total(),
        ];
    }
}
