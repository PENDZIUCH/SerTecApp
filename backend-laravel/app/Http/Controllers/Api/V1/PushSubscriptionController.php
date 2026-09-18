<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// Un solo endpoint sirve a las DOS audiencias (tecnicos en la PWA,
// sertecapp.pendziuch.com, y supervisores/admins en Filament,
// demo.pendziuch.com/sertecapp) - ambos son el mismo modelo User
// (App\Models\User, trait HasPushSubscriptions), solo cambia desde que
// origen/service worker llega la suscripcion. Requiere auth:sanctum como
// el resto de la API (ver routes/api.php).
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Datos de suscripción inválidos', 422, $validator->errors());
        }

        $request->user()->updatePushSubscription(
            endpoint: $request->input('endpoint'),
            key: $request->input('keys.p256dh'),
            token: $request->input('keys.auth'),
            contentEncoding: $request->input('contentEncoding'),
        );

        return $this->success(null, 'Suscripción a notificaciones push guardada', 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Falta el endpoint de la suscripción', 422, $validator->errors());
        }

        $request->user()->deletePushSubscription($request->input('endpoint'));

        return $this->success(null, 'Suscripción a notificaciones push eliminada');
    }
}
