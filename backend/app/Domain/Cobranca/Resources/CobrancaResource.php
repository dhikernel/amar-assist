<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CobrancaResource extends JsonResource
{
    public function toArray($request): array
    {
        $acrescimos = $this->estaPaga()
            ? [
                'dias_atraso' => $this->dias_atraso,
                'valor_multa' => $this->valor_multa,
                'valor_juros' => $this->valor_juros,
                'valor_total' => $this->valor_total,
            ]
            : $this->calcularAcrescimos();

        return [
            'id' => $this->id,
            'competencia' => $this->competencia?->format('Y-m'),
            'tipo' => $this->tipo?->value,
            'tipo_rotulo' => $this->tipo?->rotulo(),
            'data_vencimento' => $this->data_vencimento?->format('Y-m-d'),
            'dias_atraso' => $acrescimos['dias_atraso'],
            'em_atraso' => $acrescimos['dias_atraso'] > 0,
            'valor_original' => $this->valor_original,
            'valor_multa' => $acrescimos['valor_multa'],
            'valor_juros' => $acrescimos['valor_juros'],
            'valor_total' => $acrescimos['valor_total'],
            'situacao' => $this->situacao?->value,
            'situacao_rotulo' => $this->situacao?->rotulo(),
            'data_pagamento' => $this->data_pagamento?->toIso8601String(),
            'contrato' => [
                'id' => $this->contrato_id,
                'numero' => $this->whenLoaded('contrato', fn () => $this->contrato->numero),
                'cliente' => $this->whenLoaded('contrato', fn () => [
                    'id' => $this->contrato->cliente_id,
                    'nome' => $this->contrato->relationLoaded('cliente') ? $this->contrato->cliente->nome : null,
                ]),
            ],
            'detalhe' => new TipoCobrancaResource($this->whenLoaded('detalhe')),
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
