<?php

declare(strict_types=1);

namespace App\Domain\Contrato\Enums;

enum TipoContrato: string
{
    case Fisica = 'PF';
    case Juridica = 'PJ';

    public function rotulo(): string
    {
        return match ($this) {
            self::Fisica => 'Pessoa Física',
            self::Juridica => 'Pessoa Jurídica',
        };
    }
}
