<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\MagicLinkController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Sesión 2026-09 (sábado): el magic link pasó de ser un token de 365 días
 * reusable (viajaba en texto plano por WhatsApp/email) a un token de un
 * solo uso que vence en 24hs y se canjea por una sesión normal al primer
 * clic. Ver CLAUDE.md, sección "magic link".
 */
class MagicLinkSingleUseTest extends TestCase
{
    private User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'técnico', 'guard_name' => 'web']);
        $this->tecnico = User::factory()->create();
        $this->tecnico->assignRole('técnico');
    }

    private function extractToken(string $magicLink): string
    {
        $query = parse_url($magicLink, PHP_URL_QUERY);
        parse_str($query, $params);

        return $params['t'];
    }

    public function test_magic_link_expires_in_24_hours_not_365_days(): void
    {
        $magicLink = MagicLinkController::issueLinkFor($this->tecnico);
        $token = $this->tecnico->tokens()->latest()->first();

        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->lessThanOrEqualTo(now()->addHours(24)->addMinute()));
        $this->assertTrue($token->expires_at->greaterThan(now()->addHours(23)));
    }

    public function test_first_click_exchanges_magic_link_for_a_session_token(): void
    {
        $magicLink = MagicLinkController::issueLinkFor($this->tecnico);
        $rawToken = $this->extractToken($magicLink);

        $response = $this->withHeader('Authorization', "Bearer {$rawToken}")
            ->getJson('/api/v1/magic-link/verify');

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertNotEquals($rawToken, $response->json('token'));
    }

    public function test_reusing_the_same_magic_link_fails_after_first_click(): void
    {
        $magicLink = MagicLinkController::issueLinkFor($this->tecnico);
        $rawToken = $this->extractToken($magicLink);

        $this->withHeader('Authorization', "Bearer {$rawToken}")
            ->getJson('/api/v1/magic-link/verify')
            ->assertStatus(200);

        // Sanctum memoiza el guard resuelto en el primer request dentro del
        // mismo test — sin esto, el segundo request ni siquiera re-evalúa
        // el token, da un falso 200. En producción cada request HTTP es un
        // proceso nuevo, así que esto no aplica fuera de este test.
        Auth::forgetGuards();

        // El mismo link, reabierto una segunda vez (ej. alguien lo reenvía
        // o el técnico vuelve a tocarlo desde WhatsApp): ya no sirve.
        $this->withHeader('Authorization', "Bearer {$rawToken}")
            ->getJson('/api/v1/magic-link/verify')
            ->assertStatus(401);
    }

    public function test_session_token_issued_on_exchange_does_not_expire_like_the_magic_link(): void
    {
        $magicLink = MagicLinkController::issueLinkFor($this->tecnico);
        $rawToken = $this->extractToken($magicLink);

        $this->withHeader('Authorization', "Bearer {$rawToken}")
            ->getJson('/api/v1/magic-link/verify');

        $sessionToken = $this->tecnico->tokens()->where('name', 'api-token')->first();

        $this->assertNotNull($sessionToken);
        $this->assertNull($sessionToken->expires_at);
    }
}
