<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    /**
     * Crea un magic link de un solo uso, válido 24hs. Usado tanto acá como
     * en UserResource (acciones "Enviar Acceso"/WhatsApp/Email) para que
     * no haya dos criterios de expiración distintos conviviendo.
     */
    public static function issueLinkFor(User $user): string
    {
        $token = $user->createToken('magic-link', ['*'], now()->addHours(24))->plainTextToken;
        $pwaUrl = config('app.pwa_url', 'https://sertecapp.pendziuch.com');

        return "{$pwaUrl}/l?t={$token}";
    }

    public function generate(Request $request)
    {
        if (!$request->user()->hasAnyRole(['super_admin', 'administrador', 'supervisor'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'url' => static::issueLinkFor($user),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function verify(Request $request)
    {
        $magicLinkToken = $request->user()->currentAccessToken();
        $user = $request->user();

        // Canjear el magic link (un solo uso) por un token de sesión normal,
        // igual al que emite un login por email+contraseña. El magic link
        // muere acá aunque el link se vuelva a abrir después.
        $sessionToken = $user->createToken('api-token')->plainTextToken;
        $magicLinkToken?->delete();

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $sessionToken,
        ]);
    }
}
