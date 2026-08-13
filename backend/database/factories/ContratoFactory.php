<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Cliente\Models\Cliente;
use App\Domain\Contrato\Enums\SituacaoContrato;
use App\Domain\Contrato\Enums\TipoContrato;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContratoFactory extends Factory
{
    protected $model = Contrato::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'numero' => 'CT-'.fake()->unique()->numerify('######'),
            'tipo' => TipoContrato::Fisica->value,
            'ciclo' => fake()->numberBetween(1, 31),
            'valor_mensal' => fake()->randomFloat(2, 50, 5000),
            'data_inicio' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'data_fim' => null,
            'situacao' => SituacaoContrato::Ativo->value,
        ];
    }

    public function encerrado(): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => SituacaoContrato::Encerrado->value,
            'data_fim' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ]);
    }
}
