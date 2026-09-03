<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class HashLegacyPasswords extends Command
{
    protected $signature = 'app:hash-legacy-passwords';
    protected $description = 'Re-hashea contraseñas guardadas en texto plano (bug historico), sin cambiar su valor funcional';

    public function handle(): int
    {
        $fixed = 0;
        User::withTrashed()->chunkById(50, function ($users) use (&$fixed) {
            foreach ($users as $user) {
                if (!empty($user->password) && !str_starts_with($user->password, '$2y$')) {
                    $plain = $user->password;
                    $user->timestamps = false;
                    $user->forceFill(['password' => Hash::make($plain)])->save();
                    $fixed++;
                }
            }
        });

        $this->info("Contraseñas re-hasheadas: {$fixed}");
        return self::SUCCESS;
    }
}
