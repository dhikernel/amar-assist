<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SEED_USER_EMAIL', 'admin@amarassist.com.br')],
            [
                'name' => env('SEED_USER_NAME', 'Administrador'),
                'password' => env('SEED_USER_PASSWORD', 'Amar@2026'),
                'email_verified_at' => now(),
            ]
        );
    }
}
