<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Cobranca\Enums\SituacaoCobranca;
use App\Domain\Cobranca\Enums\TipoCobranca;
use App\Domain\Cobranca\Models\Cobranca;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CobrancaFactory extends Factory
{
    protected $model = Cobranca::class;

    public function definition(): array
    {
        $competencia = Carbon::today()->startOfMonth();

        return [
            'contrato_id' => Contrato::factory(),
            'competencia' => $competencia->toDateString(),
            'tipo' => TipoCobranca::Boleto->value,
            'data_vencimento' => $competencia->copy()->addDays(9)->toDateString(),
            'valor_original' => 249.90,
            'valor_total' => 249.90,
            'situacao' => SituacaoCobranca::Aberta->value,
        ];
    }

    public function emAtraso(int $dias = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'data_vencimento' => Carbon::today()->subDays($dias)->toDateString(),
        ]);
    }

    public function aVencer(int $dias = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'data_vencimento' => Carbon::today()->addDays($dias)->toDateString(),
        ]);
    }

    public function paga(): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => SituacaoCobranca::Paga->value,
            'data_pagamento' => now(),
        ]);
    }

    public function pix(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoCobranca::Pix->value,
        ]);
    }

    public function cartao(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoCobranca::Cartao->value,
        ]);
    }
}
