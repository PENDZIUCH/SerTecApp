<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Motor de agenda/reservas GENERICO (ver App\Models\Booking). No conoce
 * "tecnico" ni "orden de trabajo" — solo resource/subject polimorficos.
 * La autofiltracion de un usuario con rol tecnico a "sus" bookings asume
 * que, cuando lo es, el resource reservado es el propio usuario
 * (App\Models\User) — es el unico punto donde este controller genérico
 * conoce el caso de uso de SerTecApp, para que un técnico no vea la
 * agenda ajena.
 */
class BookingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private BookingService $bookingService
    ) {
        $this->authorizeResource(Booking::class, 'booking');
    }

    public function index()
    {
        // subject.customer/equipment: para que la PWA pueda mostrar la orden
        // con datos reales (cliente, direccion, prioridad) en vez de solo el
        // numero de orden - pedido de Hugo, "Mi Agenda" tiene que verse como
        // una orden de trabajo real, no un codigo pelado.
        $query = Booking::with(['resource', 'subject.customer', 'subject.equipment']);

        if (auth()->user()->hasAnyRole(['técnico', 'tecnico'])) {
            $query->where('resource_type', User::class)
                ->where('resource_id', auth()->id());
        }

        if (request('resource_type') && request('resource_id')) {
            $query->where('resource_type', request('resource_type'))
                ->where('resource_id', request('resource_id'));
        }

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('from')) {
            $query->where('starts_at', '>=', request('from'));
        }
        if (request('to')) {
            $query->where('starts_at', '<=', request('to'));
        }

        $bookings = $query->orderBy('starts_at')->paginate(request('per_page', 15));

        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->create($request->validated());

        return response()->json(new BookingResource($booking), 201);
    }

    public function show(Booking $booking)
    {
        return new BookingResource($booking->load(['resource', 'subject']));
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $booking = $this->bookingService->update($booking, $request->all());

        return response()->json(new BookingResource($booking));
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $this->bookingService->delete($booking);

        return response()->json(null, 204);
    }

    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);

        $booking = $this->bookingService->checkIn($booking, $request->only(['latitude', 'longitude']));

        return response()->json(new BookingResource($booking));
    }

    public function checkOut(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);

        $booking = $this->bookingService->checkOut($booking, $request->only(['latitude', 'longitude']));

        return response()->json(new BookingResource($booking));
    }
}
