<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkshopItemResource;
use App\Models\WorkshopItem;
use App\Services\WorkshopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function __construct(
        private WorkshopService $workshopService
    ) {}

    public function index()
    {
        $items = WorkshopItem::with(['equipment', 'customer', 'assignedTech'])
            ->applyFilters(request()->all())
            ->paginate(request('per_page', 15));

        return WorkshopItemResource::collection($items);
    }

    public function store(Request $request): JsonResponse
    {
        $item = $this->workshopService->create($request->all());

        return response()->json(new WorkshopItemResource($item), 201);
    }

    public function show(WorkshopItem $workshop)
    {
        return new WorkshopItemResource($workshop->load(['equipment', 'customer', 'workOrder']));
    }

    public function update(Request $request, WorkshopItem $workshop): JsonResponse
    {
        $item = $this->workshopService->update($workshop, $request->all());

        return response()->json(new WorkshopItemResource($item));
    }

    public function destroy(WorkshopItem $workshop): JsonResponse
    {
        $this->workshopService->delete($workshop);

        return response()->json(null, 204);
    }

    public function changeStatus(Request $request, WorkshopItem $workshop): JsonResponse
    {
        $item = $this->workshopService->changeStatus($workshop, $request->input('status'));

        return response()->json(new WorkshopItemResource($item));
    }
}
