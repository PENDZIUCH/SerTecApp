<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function __construct(
        private WorkOrderService $workOrderService
    ) {}

    public function index()
    {
        $workOrders = WorkOrder::with(['customer', 'equipment', 'assignedTech'])
            ->applyFilters(request()->all())
            ->paginate(request('per_page', 15));

        return WorkOrderResource::collection($workOrders);
    }

    public function store(StoreWorkOrderRequest $request): JsonResponse
    {
        $workOrder = $this->workOrderService->create($request->validated());

        return response()->json(new WorkOrderResource($workOrder), 201);
    }

    public function show(WorkOrder $workOrder)
    {
        return new WorkOrderResource($workOrder->load(['customer', 'equipment', 'assignedTech', 'logs', 'partsUsed']));
    }

    public function update(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $workOrder = $this->workOrderService->update($workOrder, $request->all());

        return response()->json(new WorkOrderResource($workOrder));
    }

    public function destroy(WorkOrder $workOrder): JsonResponse
    {
        $this->workOrderService->delete($workOrder);

        return response()->json(null, 204);
    }

    public function changeStatus(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $workOrder = $this->workOrderService->changeStatus($workOrder, $request->input('status'));

        return response()->json(new WorkOrderResource($workOrder));
    }

    public function addPart(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $part = $this->workOrderService->addPart(
            $workOrder,
            $request->input('part_id'),
            $request->input('quantity'),
            $request->input('unit_cost')
        );

        return response()->json($part, 201);
    }
}
