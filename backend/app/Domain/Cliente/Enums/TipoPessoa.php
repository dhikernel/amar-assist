<?php

declare(strict_types=1);

namespace App\Domain\Cliente\Enums;

enum TipoPessoa: string
{
    case Fisica = 'PF';
    case Juridica = 'PJ';

    public function quantidadeDeDigitos(): int
    {
        return match ($this) {
            self::Fisica => 11,
            self::Juridica => 14,
        };
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Fisica => 'Pessoa Física',
            self::Juridica => 'Pessoa Jurídica',
        };
    }

    public static function pelaQuantidadeDeDigitos(string $documento): ?self
    {
        return match (strlen($documento)) {
            11 => self::Fisica,
            14 => self::Juridica,
            default => null,
        };
    }
}
