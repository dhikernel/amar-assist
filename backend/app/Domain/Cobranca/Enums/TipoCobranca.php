<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Enums;

enum TipoCobranca: string
{
    case Boleto = 'boleto';
    case Cartao = 'cartao';
    case Pix = 'pix';

    public function rotulo(): string
    {
        return match ($this) {
            self::Boleto => 'Boleto',
            self::Cartao => 'Cartão',
            self::Pix => 'Pix',
        };
    }
}
