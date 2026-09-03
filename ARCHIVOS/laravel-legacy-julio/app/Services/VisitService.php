<?php

namespace App\Services;

use App\Models\Visit;

class VisitService
{
    public function create(array $data)
    {
        $data['created_by'] = auth()->id();
        return Visit::create($data);
    }

    public function update(Visit $visit, array $data)
    {
        $data['updated_by'] = auth()->id();
        $visit->update($data);
        return $visit->fresh();
    }

    public function delete(Visit $visit)
    {
        return $visit->delete();
    }

    public function checkIn(Visit $visit, array $data = [])
    {
        $visit->update([
            'check_in' => now(),
            'status' => 'in_progress',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return $visit;
    }

    public function checkOut(Visit $visit)
    {
        $checkIn = $visit->check_in;
        $checkOut = now();
        $duration = $checkIn ? $checkIn->diffInMinutes($checkOut) : null;

        $visit->update([
            'check_out' => $checkOut,
            'status' => 'completed',
            'duration_minutes' => $duration,
        ]);

        if ($visit->subscription_id) {
            $visit->subscription->increment('visits_used');
        }

        return $visit;
    }
}
