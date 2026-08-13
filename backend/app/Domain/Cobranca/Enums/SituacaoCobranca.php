<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Enums;

enum SituacaoCobranca: string
{
    case Aberta = 'aberta';
    case Paga = 'paga';

    public function rotulo(): string
    {
        return match ($this) {
            self::Aberta => 'Aberta',
            self::Paga => 'Paga',
        };
    }

    public function estaAberta(): bool
    {
        return $this === self::Aberta;
    }
}
