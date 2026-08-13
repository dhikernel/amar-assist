<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Cliente\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        if (Cliente::query()->exists()) {
            return;
        }

        Cliente::factory()->count(12)->create();
        Cliente::factory()->juridica()->count(6)->create();
        Cliente::factory()->inativo()->count(3)->create();
    }
}
