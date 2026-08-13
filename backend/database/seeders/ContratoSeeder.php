<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Cliente\Models\Cliente;
use App\Domain\Contrato\Enums\SituacaoContrato;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Database\Seeder;

class ContratoSeeder extends Seeder
{
    public function run(): void
    {
        if (Contrato::query()->exists()) {
            return;
        }

        $clientes = Cliente::query()
            ->where('situacao', 'ativo')
            ->take(10)
            ->get();

        $numero = 1;

        foreach ($clientes as $indice => $cliente) {
            Contrato::factory()->create([
                'cliente_id' => $cliente->id,
                'numero' => sprintf('CT-%06d', $numero++),
                'ciclo' => [5, 10, 15, 20, 28, 29, 30, 31][$indice % 8],
                'situacao' => $indice % 5 === 0
                    ? SituacaoContrato::Suspenso->value
                    : SituacaoContrato::Ativo->value,
            ]);
        }
    }
}
