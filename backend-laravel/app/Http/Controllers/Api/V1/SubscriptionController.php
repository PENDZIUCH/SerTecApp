<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function index()
    {
        $subscriptions = Subscription::with('customer')
            ->applyFilters(request()->all())
            ->paginate(request('per_page', 15));

        return SubscriptionResource::collection($subscriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $subscription = $this->subscriptionService->create($request->all());

        return response()->json(new SubscriptionResource($subscription), 201);
    }

    public function show(Subscription $subscription)
    {
        return new SubscriptionResource($subscription->load(['customer', 'renewalHistory']));
    }

    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService->update($subscription, $request->all());

        return response()->json(new SubscriptionResource($subscription));
    }

    public function destroy(Subscription $subscription): JsonResponse
    {
        $this->subscriptionService->delete($subscription);

        return response()->json(null, 204);
    }

    public function renew(Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService->renew($subscription);

        return response()->json(new SubscriptionResource($subscription));
    }
}
