<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartRequest;
use App\Http\Resources\PartResource;
use App\Models\Part;
use App\Services\PartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartController extends Controller
{
    public function __construct(
        private PartService $partService
    ) {}

    public function index()
    {
        $parts = Part::applyFilters(request()->all())
            ->paginate(request('per_page', 15));

        return PartResource::collection($parts);
    }

    public function store(StorePartRequest $request): JsonResponse
    {
        $part = $this->partService->create($request->validated());

        return response()->json(new PartResource($part), 201);
    }

    public function show(Part $part)
    {
        return new PartResource($part->load('movements'));
    }

    public function update(Request $request, Part $part): JsonResponse
    {
        $part = $this->partService->update($part, $request->all());

        return response()->json(new PartResource($part));
    }

    public function destroy(Part $part): JsonResponse
    {
        $this->partService->delete($part);

        return response()->json(null, 204);
    }

    public function addMovement(Request $request, Part $part): JsonResponse
    {
        $movement = $this->partService->addMovement(
            $part,
            $request->input('movement_type'),
            $request->input('quantity'),
            $request->only(['description', 'related_work_order_id'])
        );

        return response()->json($movement, 201);
    }
}
