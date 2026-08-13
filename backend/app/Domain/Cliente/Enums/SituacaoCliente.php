<?php

declare(strict_types=1);

namespace App\Domain\Cliente\Enums;

enum SituacaoCliente: string
{
    case Ativo = 'ativo';
    case Inativo = 'inativo';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
        };
    }

    public function estaAtivo(): bool
    {
        return $this === self::Ativo;
    }
}
