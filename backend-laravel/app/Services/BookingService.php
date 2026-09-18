<?php

namespace App\Services;

use App\Models\Booking;

class BookingService
{
    public function create(array $data): Booking
    {
        $data['created_by'] = auth()->id();

        return Booking::create($data);
    }

    public function update(Booking $booking, array $data): Booking
    {
        $booking->update($data);

        return $booking->fresh();
    }

    public function delete(Booking $booking): bool
    {
        return (bool) $booking->delete();
    }

    public function checkIn(Booking $booking, array $data = []): Booking
    {
        $booking->update([
            'check_in' => now(),
            'status' => 'in_progress',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return $booking;
    }

    public function checkOut(Booking $booking, array $data = []): Booking
    {
        $checkOut = now();
        $duration = $booking->check_in ? $booking->check_in->diffInMinutes($checkOut) : null;

        $booking->update([
            'check_out' => $checkOut,
            'status' => 'completed',
            'latitude' => $data['latitude'] ?? $booking->latitude,
            'longitude' => $data['longitude'] ?? $booking->longitude,
        ]);

        // duration_minutes no es una columna propia (se calcula on-the-fly
        // via calculateDuration()) para no duplicar check_in/check_out;
        // se deja aca documentado por si a futuro conviene persistirla.
        unset($duration);

        return $booking->fresh();
    }
}
