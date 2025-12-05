<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService
    ) {}

    public function index()
    {
        $budgets = Budget::with(['customer', 'items'])
            ->applyFilters(request()->all())
            ->paginate(request('per_page', 15));

        return BudgetResource::collection($budgets);
    }

    public function store(Request $request): JsonResponse
    {
        $budget = $this->budgetService->create($request->all());

        return response()->json(new BudgetResource($budget), 201);
    }

    public function show(Budget $budget)
    {
        return new BudgetResource($budget->load(['customer', 'items']));
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        $budget = $this->budgetService->update($budget, $request->all());

        return response()->json(new BudgetResource($budget));
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $this->budgetService->delete($budget);

        return response()->json(null, 204);
    }

    public function approve(Budget $budget): JsonResponse
    {
        $budget = $this->budgetService->approve($budget);

        return response()->json(new BudgetResource($budget));
    }

    public function reject(Request $request, Budget $budget): JsonResponse
    {
        $budget = $this->budgetService->reject($budget, $request->input('reason'));

        return response()->json(new BudgetResource($budget));
    }
}
