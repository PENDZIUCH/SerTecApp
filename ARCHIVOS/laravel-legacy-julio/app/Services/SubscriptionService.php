<?php

namespace App\Services;

use App\Models\Subscription;

class SubscriptionService
{
    public function create(array $data)
    {
        $data['created_by'] = auth()->id();
        return Subscription::create($data);
    }

    public function update(Subscription $subscription, array $data)
    {
        $data['updated_by'] = auth()->id();
        $subscription->update($data);
        return $subscription->fresh();
    }

    public function delete(Subscription $subscription)
    {
        return $subscription->delete();
    }

    public function renew(Subscription $subscription)
    {
        $previousRenewalDate = $subscription->renewal_date;
        $newRenewalDate = $this->calculateNextRenewalDate($subscription);

        $subscription->update([
            'renewal_date' => $newRenewalDate,
            'visits_used' => 0,
        ]);

        $subscription->renewalHistory()->create([
            'renewal_date' => $newRenewalDate,
            'previous_renewal_date' => $previousRenewalDate,
            'visits_reset' => $subscription->visits_per_period,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return $subscription->fresh();
    }

    private function calculateNextRenewalDate(Subscription $subscription)
    {
        $currentDate = $subscription->renewal_date;

        return match($subscription->billing_cycle) {
            'monthly' => $currentDate->addMonth(),
            'quarterly' => $currentDate->addMonths(3),
            'yearly' => $currentDate->addYear(),
            default => $currentDate->addMonth(),
        };
    }
}
