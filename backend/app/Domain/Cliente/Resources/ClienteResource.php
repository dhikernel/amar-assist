<?php

declare(strict_types=1);

namespace App\Domain\Cliente\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'tipo_pessoa' => $this->tipo_pessoa?->value,
            'tipo_pessoa_rotulo' => $this->tipo_pessoa?->rotulo(),
            'cpf_cnpj' => $this->cpf_cnpj,
            'cpf_cnpj_formatado' => $this->cpf_cnpj_formatado,
            'endereco' => [
                'cep' => $this->cep,
                'cep_formatado' => $this->cep_formatado,
                'logradouro' => $this->logradouro,
                'numero' => $this->numero,
                'complemento' => $this->complemento,
                'bairro' => $this->bairro,
                'cidade' => $this->cidade,
                'uf' => $this->uf,
            ],
            'contato' => [
                'email' => $this->email,
                'telefone' => $this->telefone,
                'telefone_formatado' => $this->telefone_formatado,
            ],
            'situacao' => $this->situacao?->value,
            'situacao_rotulo' => $this->situacao?->rotulo(),
            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }
}
