<?php

declare(strict_types=1);

namespace App\Domain\Shared\Rules;

use Illuminate\Contracts\Validation\Rule;

class CpfCnpj implements Rule
{
    public function passes($attribute, $value): bool
    {
        $digitos = self::apenasDigitos((string) $value);

        return match (strlen($digitos)) {
            11 => self::cpfValido($digitos),
            14 => self::cnpjValido($digitos),
            default => false,
        };
    }

    public function message(): string
    {
        return 'O CPF/CNPJ informado é inválido.';
    }

    public static function apenasDigitos(string $valor): string
    {
        return preg_replace('/\D/', '', $valor) ?? '';
    }

    public static function cpfValido(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        foreach ([9, 10] as $posicao) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * ($posicao + 1 - $i);
            }

            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;

            if ((int) $cpf[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    public static function cnpjValido(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        foreach ([12, 13] as $posicao) {
            $soma = 0;
            $pesosAtuais = array_slice($pesos, 13 - $posicao);

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cnpj[$i] * $pesosAtuais[$i];
            }

            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;

            if ((int) $cnpj[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }
}
