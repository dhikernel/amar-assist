<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Resources;

use App\Domain\Cobranca\Enums\TipoCobranca;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoCobrancaResource extends JsonResource
{
    public function toArray($request): array
    {
        return match ($this->cobranca?->tipo) {
            TipoCobranca::Boleto => [
                'codigo_barras' => $this->codigo_barras,
                'linha_digitavel' => $this->linha_digitavel,
            ],
            TipoCobranca::Cartao => [
                'bandeira' => $this->cartao_bandeira,
                'titular' => $this->cartao_titular,
                'ultimos_digitos' => $this->cartao_ultimos_digitos,
                'validade' => $this->cartao_validade,
            ],
            TipoCobranca::Pix => [
                'tipo_chave' => $this->pix_tipo_chave?->value,
                'tipo_chave_rotulo' => $this->pix_tipo_chave?->rotulo(),
                'chave' => $this->pix_chave,
            ],
            default => [],
        };
    }
}
