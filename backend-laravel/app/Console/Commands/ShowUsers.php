<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowUsers extends Command
{
    protected $signature = 'show:users';
    protected $description = 'Mostrar usuarios';

    public function handle()
    {
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $this->info("ID: {$user->id} - {$user->name} ({$user->email})");
        }

        return 0;
    }
}
