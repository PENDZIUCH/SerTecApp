<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartRequest;
use App\Http\Resources\PartResource;
use App\Models\Part;
use App\Services\PartService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PartService $partService
    ) {
        $this->authorizeResource(Part::class, 'part');
    }

    public function index()
    {
        $query = Part::query();

        if (request('search')) {
            $query->where('name', 'like', '%' . request('search') . '%')
                  ->orWhere('sku', 'like', '%' . request('search') . '%');
        }
        if (request('category')) {
            $query->where('category', request('category'));
        }

        $parts = $query->paginate(request('per_page', 15));

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
        $this->authorize('update', $part);

        $movement = $this->partService->addMovement(
            $part,
            $request->input('movement_type'),
            $request->input('quantity'),
            $request->only(['description', 'related_work_order_id'])
        );

        return response()->json($movement, 201);
    }
}
