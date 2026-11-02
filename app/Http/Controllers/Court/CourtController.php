<?php

namespace App\Http\Controllers\Court;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourtResource;
use App\Services\Court\CourtService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourtController extends Controller
{
    public function __construct(private readonly CourtService $courts) {}

    public function index(): AnonymousResourceCollection
    {
        return CourtResource::collection($this->courts->paginateActive());
    }

    public function show(string $court): CourtResource
    {
        $model = $this->courts->findActiveBySlug($court);
        abort_if($model === null, 404);

        return new CourtResource($model);
    }
}
