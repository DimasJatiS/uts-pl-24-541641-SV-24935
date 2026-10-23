<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHoldingRequest;
use App\Http\Requests\UpdateHoldingRequest;
use App\Http\Resources\HoldingResource;
use App\Models\Holding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * CRUD holding milik user yang sedang login.
 *
 * Setiap query selalu lewat relasi $request->user()->holdings(), jadi holding milik
 * user lain tidak akan pernah ditemukan dan otomatis dibalas 404 (bukan 403),
 * sehingga orang tidak bisa menebak ID mana saja yang ada.
 */
class HoldingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $holdings = $request->user()->holdings()
            ->with('coin')
            ->orderBy('coin_id')
            ->get();

        return HoldingResource::collection($holdings);
    }

    public function store(StoreHoldingRequest $request): JsonResponse
    {
        $holding = $request->user()->holdings()->create($request->validated());

        return (new HoldingResource($holding->fresh('coin')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $holding): HoldingResource
    {
        return new HoldingResource($this->findOwned($request, $holding));
    }

    public function update(UpdateHoldingRequest $request, string $holding): HoldingResource
    {
        $model = $this->findOwned($request, $holding);
        $model->update($request->validated());

        return new HoldingResource($model->fresh('coin'));
    }

    public function destroy(Request $request, string $holding): JsonResponse
    {
        $this->findOwned($request, $holding)->delete();

        return response()->json(['message' => 'Holding dihapus.']);
    }

    private function findOwned(Request $request, string $id): Holding
    {
        /** @var Holding */
        return $request->user()->holdings()->with('coin')->findOrFail($id);
    }
}
