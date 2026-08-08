<?php

namespace App\Services;

use App\Mail\AccesoUsuarioMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }
        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }
        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function assignRole(User $user, string $role): User
    {
        $user->assignRole($role);
        return $user->fresh('roles');
    }

    public function syncPermissions(User $user, array $permissions): User
    {
        $user->syncPermissions($permissions);
        return $user->fresh('permissions');
    }

    public function generateMagicLink(User $user, int $days = 30): string
    {
        $token = $user->createToken('magic-link', ['*'], now()->addDays($days))->plainTextToken;
        $pwaUrl = rtrim(config('app.pwa_url', 'http://localhost:3000'), '/');
        return $pwaUrl . '/l?t=' . $token;
    }

    public function sendAccessEmail(User $user): void
    {
        $url = $this->generateMagicLink($user);
        Mail::to($user->email)->send(new AccesoUsuarioMail($user, $url));
    }

    public function formatWhatsappNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (!str_starts_with($phone, '54')) {
            if (str_starts_with($phone, '11') || str_starts_with($phone, '9')) {
                $phone = '54' . $phone;
            } else {
                $phone = '549' . $phone;
            }
        }
        return $phone;
    }

    public function buildWhatsappAccessUrl(User $user, string $accessUrl): string
    {
        $phone = $this->formatWhatsappNumber($user->phone ?? '');
        $message = urlencode(
            "Hola {$user->name}!\n\n" .
            "Tu acceso a la app:\n\n" .
            "{$accessUrl}\n\n" .
            "(Guarda este link para acceder siempre)"
        );
        return "https://wa.me/{$phone}?text={$message}";
    }
}