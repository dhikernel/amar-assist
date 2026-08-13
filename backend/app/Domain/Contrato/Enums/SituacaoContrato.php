<?php

declare(strict_types=1);

namespace App\Domain\Contrato\Enums;

enum SituacaoContrato: string
{
    case Ativo = 'ativo';
    case Suspenso = 'suspenso';
    case Encerrado = 'encerrado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Suspenso => 'Suspenso',
            self::Encerrado => 'Encerrado',
        };
    }
}
