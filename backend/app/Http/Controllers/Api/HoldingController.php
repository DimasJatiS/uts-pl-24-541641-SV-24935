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
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HoldingController extends Controller
{
    /**
     * GET /api/holdings → Mengambil daftar aset/holding milik user terautentikasi.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $holdings = $request->user()->holdings()
            ->with('coin')
            ->orderBy('coin_id')
            ->get();

        return HoldingResource::collection($holdings);
    }

    /**
     * POST /api/holdings → Menambah holding baru.
     */
    public function store(StoreHoldingRequest $request): JsonResponse
    {
        $holding = $request->user()->holdings()->create($request->validated());

        Log::info('holding.created', [
            'actor_type'  => 'authenticated_user',
            'user_id'     => $request->user()->id,
            'action'      => 'create_holding',
            'resource'    => 'holding',
            'resource_id' => $holding->id,
            'reason'      => 'holding_saved_successfully',
        ]);

        return (new HoldingResource($holding->fresh('coin')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/holdings/{holding} → Detail holding spesifik milik user.
     */
    public function show(Request $request, string $holding): HoldingResource
    {
        return new HoldingResource($this->findOwned($request, $holding));
    }

    /**
     * PUT/PATCH /api/holdings/{holding} → Memperbarui jumlah/data holding.
     */
    public function update(UpdateHoldingRequest $request, string $holding): HoldingResource
    {
        $model = $this->findOwned($request, $holding);
        $model->update($request->validated());

        Log::info('holding.updated', [
            'actor_type'  => 'authenticated_user',
            'user_id'     => $request->user()->id,
            'action'      => 'update_holding',
            'resource'    => 'holding',
            'resource_id' => $model->id,
            'reason'      => 'holding_updated_successfully',
        ]);

        return new HoldingResource($model->fresh('coin'));
    }

    /**
     * DELETE /api/holdings/{holding} → Menghapus holding milik user.
     */
    public function destroy(Request $request, string $holding): JsonResponse
    {
        $model = $this->findOwned($request, $holding);
        $holdingId = $model->id;

        $model->delete();

        Log::info('holding.deleted', [
            'actor_type'  => 'authenticated_user',
            'user_id'     => $request->user()->id,
            'action'      => 'delete_holding',
            'resource'    => 'holding',
            'resource_id' => $holdingId,
            'reason'      => 'holding_deleted_successfully',
        ]);

        return response()->json(['message' => 'Holding dihapus.'], Response::HTTP_OK);
    }

    /**
     * Helper privat untuk memastikan resource yang diakses benar-benar milik user (Ownership Guard).
     */
    private function findOwned(Request $request, string $id): Holding
    {
        /** @var Holding */
        return $request->user()->holdings()->with('coin')->findOrFail($id);
    }
}