<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use App\Services\EquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function __construct(
        private EquipmentService $equipmentService
    ) {}

    public function index()
    {
        $equipment = Equipment::with(['customer', 'brand', 'model'])
            ->applyFilters(request()->all())
            ->paginate(request('per_page', 15));

        return EquipmentResource::collection($equipment);
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $equipment = $this->equipmentService->create($request->validated());

        return response()->json(new EquipmentResource($equipment), 201);
    }

    public function show(Equipment $equipment)
    {
        return new EquipmentResource($equipment->load(['customer', 'brand', 'model', 'history']));
    }

    public function update(Request $request, Equipment $equipment): JsonResponse
    {
        $equipment = $this->equipmentService->update($equipment, $request->all());

        return response()->json(new EquipmentResource($equipment));
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $this->equipmentService->delete($equipment);

        return response()->json(null, 204);
    }

    public function changeStatus(Request $request, Equipment $equipment): JsonResponse
    {
        $equipment = $this->equipmentService->changeStatus(
            $equipment,
            $request->input('status'),
            $request->input('description')
        );

        return response()->json(new EquipmentResource($equipment));
    }
}
