<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Shared\Rules\CpfCnpj;
use PHPUnit\Framework\TestCase;

class CpfCnpjTest extends TestCase
{
    /** @dataProvider cpfsValidos */
    public function test_aceita_cpf_valido(string $cpf): void
    {
        $this->assertTrue((new CpfCnpj)->passes('cpf_cnpj', $cpf));
    }

    /** @dataProvider cpfsInvalidos */
    public function test_recusa_cpf_invalido(string $cpf): void
    {
        $this->assertFalse((new CpfCnpj)->passes('cpf_cnpj', $cpf));
    }

    /** @dataProvider cnpjsValidos */
    public function test_aceita_cnpj_valido(string $cnpj): void
    {
        $this->assertTrue((new CpfCnpj)->passes('cpf_cnpj', $cnpj));
    }

    /** @dataProvider cnpjsInvalidos */
    public function test_recusa_cnpj_invalido(string $cnpj): void
    {
        $this->assertFalse((new CpfCnpj)->passes('cpf_cnpj', $cnpj));
    }

    public function test_aceita_documento_com_mascara(): void
    {
        $this->assertTrue((new CpfCnpj)->passes('cpf_cnpj', '111.444.777-35'));
        $this->assertTrue((new CpfCnpj)->passes('cpf_cnpj', '11.222.333/0001-81'));
    }

    public function test_recusa_quantidade_de_digitos_diferente_de_11_ou_14(): void
    {
        $this->assertFalse((new CpfCnpj)->passes('cpf_cnpj', '1114447773'));
        $this->assertFalse((new CpfCnpj)->passes('cpf_cnpj', '111444777355'));
        $this->assertFalse((new CpfCnpj)->passes('cpf_cnpj', ''));
    }

    public function test_apenas_digitos_remove_mascara(): void
    {
        $this->assertSame('11144477735', CpfCnpj::apenasDigitos('111.444.777-35'));
        $this->assertSame('11222333000181', CpfCnpj::apenasDigitos('11.222.333/0001-81'));
    }

    public static function cpfsValidos(): array
    {
        return [
            ['11144477735'],
            ['52998224725'],
            ['16899535009'],
        ];
    }

    public static function cpfsInvalidos(): array
    {
        return [
            'digito verificador errado' => ['11144477736'],
            'todos os digitos iguais' => ['11111111111'],
            'zeros' => ['00000000000'],
            'sequencial' => ['12345678901'],
        ];
    }

    public static function cnpjsValidos(): array
    {
        return [
            ['11222333000181'],
            ['34028316000103'],
        ];
    }

    public static function cnpjsInvalidos(): array
    {
        return [
            'digito verificador errado' => ['11222333000182'],
            'todos os digitos iguais' => ['11111111111111'],
            'zeros' => ['00000000000000'],
        ];
    }
}
