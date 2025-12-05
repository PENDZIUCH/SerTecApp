<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVisitRequest;
use App\Http\Resources\VisitResource;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function __construct(
        private VisitService $visitService
    ) {}

    public function index()
    {
        $visits = Visit::with(['workOrder', 'assignedTech'])
            ->applyFilters(request()->all())
            ->paginate(request('per_page', 15));

        return VisitResource::collection($visits);
    }

    public function store(StoreVisitRequest $request): JsonResponse
    {
        $visit = $this->visitService->create($request->validated());

        return response()->json(new VisitResource($visit), 201);
    }

    public function show(Visit $visit)
    {
        return new VisitResource($visit->load(['workOrder', 'assignedTech']));
    }

    public function update(Request $request, Visit $visit): JsonResponse
    {
        $visit = $this->visitService->update($visit, $request->all());

        return response()->json(new VisitResource($visit));
    }

    public function destroy(Visit $visit): JsonResponse
    {
        $this->visitService->delete($visit);

        return response()->json(null, 204);
    }

    public function checkIn(Request $request, Visit $visit): JsonResponse
    {
        $visit = $this->visitService->checkIn($visit, $request->only(['latitude', 'longitude']));

        return response()->json(new VisitResource($visit));
    }

    public function checkOut(Visit $visit): JsonResponse
    {
        $visit = $this->visitService->checkOut($visit);

        return response()->json(new VisitResource($visit));
    }
}
