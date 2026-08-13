<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Cobranca\Enums\SituacaoCobranca;
use App\Domain\Cobranca\Enums\TipoCobranca;
use App\Domain\Cobranca\Models\Cobranca;
use App\Domain\Contrato\Enums\SituacaoContrato;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CobrancaSeeder extends Seeder
{
    public function run(): void
    {
        if (Cobranca::query()->exists()) {
            return;
        }

        $contratos = Contrato::query()
            ->where('situacao', SituacaoContrato::Ativo->value)
            ->get();

        foreach ($contratos as $indice => $contrato) {
            foreach ([2, 1, 0] as $mesesAtras) {
                $competencia = Carbon::today()->startOfMonth()->subMonths($mesesAtras);
                $vencimento = $contrato->vencimentoPara($competencia);

                $cobranca = new Cobranca([
                    'contrato_id' => $contrato->id,
                    'competencia' => $competencia->toDateString(),
                    'tipo' => $this->tipo($indice, $mesesAtras),
                    'data_vencimento' => $vencimento->toDateString(),
                    'valor_original' => $contrato->valor_mensal,
                ]);

                if ($mesesAtras === 2) {
                    $cobranca->situacao = SituacaoCobranca::Paga;
                    $cobranca->data_pagamento = $vencimento->copy()->addDay();
                }

                $cobranca->aplicarAcrescimos()->save();
                $cobranca->detalhe()->create($this->detalhe($cobranca));
            }
        }
    }

    private function tipo(int $indice, int $mesesAtras): string
    {
        return [
            TipoCobranca::Boleto->value,
            TipoCobranca::Pix->value,
            TipoCobranca::Cartao->value,
        ][($indice + $mesesAtras) % 3];
    }

    private function detalhe(Cobranca $cobranca): array
    {
        return match ($cobranca->tipo) {
            TipoCobranca::Boleto => [
                'codigo_barras' => fake()->numerify(str_repeat('#', 44)),
                'linha_digitavel' => fake()->numerify(str_repeat('#', 47)),
            ],
            TipoCobranca::Cartao => [
                'cartao_bandeira' => fake()->randomElement(['Visa', 'Mastercard', 'Elo']),
                'cartao_titular' => mb_strtoupper(fake()->name()),
                'cartao_numero' => fake()->numerify('################'),
                'cartao_ultimos_digitos' => fake()->numerify('####'),
                'cartao_validade' => fake()->numberBetween(1, 12).'/'.fake()->numberBetween(2028, 2032),
            ],
            TipoCobranca::Pix => [
                'pix_tipo_chave' => 'email',
                'pix_chave' => fake()->unique()->safeEmail(),
            ],
        };
    }
}
