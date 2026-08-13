<?php

declare(strict_types=1);

namespace App\Domain\Contrato\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContratoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'tipo' => $this->tipo?->value,
            'tipo_rotulo' => $this->tipo?->rotulo(),
            'cliente' => [
                'id' => $this->cliente_id,
                'nome' => $this->whenLoaded('cliente', fn () => $this->cliente->nome),
                'cpf_cnpj_formatado' => $this->whenLoaded('cliente', fn () => $this->cliente->cpf_cnpj_formatado),
            ],
            'ciclo' => $this->ciclo,
            'proximo_vencimento' => $this->proximoVencimento()->format('Y-m-d'),
            'valor_mensal' => $this->valor_mensal,
            'data_inicio' => $this->data_inicio?->format('Y-m-d'),
            'data_fim' => $this->data_fim?->format('Y-m-d'),
            'situacao' => $this->situacao?->value,
            'situacao_rotulo' => $this->situacao?->rotulo(),
            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }
}
