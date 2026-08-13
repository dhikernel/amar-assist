<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Contrato\Models\Contrato;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CicloVencimentoTest extends TestCase
{
    private function contratoComCiclo(int $ciclo): Contrato
    {
        $contrato = new Contrato;
        $contrato->ciclo = $ciclo;

        return $contrato;
    }

    /** @dataProvider ciclosEMeses */
    public function test_resolve_o_vencimento_respeitando_os_dias_do_mes(
        int $ciclo,
        string $mes,
        string $esperado
    ): void {
        $vencimento = $this->contratoComCiclo($ciclo)->vencimentoPara(Carbon::parse($mes));

        $this->assertSame($esperado, $vencimento->format('Y-m-d'));
    }

    public static function ciclosEMeses(): array
    {
        return [
            'ciclo 31 em janeiro' => [31, '2027-01-10', '2027-01-31'],
            'ciclo 31 em fevereiro comum' => [31, '2027-02-10', '2027-02-28'],
            'ciclo 31 em fevereiro bissexto' => [31, '2028-02-10', '2028-02-29'],
            'ciclo 31 em abril' => [31, '2027-04-10', '2027-04-30'],
            'ciclo 30 em fevereiro comum' => [30, '2027-02-10', '2027-02-28'],
            'ciclo 30 em fevereiro bissexto' => [30, '2028-02-10', '2028-02-29'],
            'ciclo 29 em fevereiro comum' => [29, '2027-02-10', '2027-02-28'],
            'ciclo 29 em fevereiro bissexto' => [29, '2028-02-10', '2028-02-29'],
            'ciclo 28 em fevereiro comum' => [28, '2027-02-10', '2027-02-28'],
            'ciclo 15 em qualquer mes' => [15, '2027-02-10', '2027-02-15'],
            'ciclo 1 no primeiro dia' => [1, '2027-02-10', '2027-02-01'],
            'ciclo 31 em mes de 30 dias (junho)' => [31, '2027-06-01', '2027-06-30'],
            'ciclo 31 em mes de 31 dias (julho)' => [31, '2027-07-01', '2027-07-31'],
        ];
    }

    public function test_ano_secular_nao_bissexto_tem_fevereiro_de_28_dias(): void
    {
        $vencimento = $this->contratoComCiclo(31)->vencimentoPara(Carbon::parse('2100-02-10'));

        $this->assertSame('2100-02-28', $vencimento->format('Y-m-d'));
    }

    public function test_ano_secular_bissexto_tem_fevereiro_de_29_dias(): void
    {
        $vencimento = $this->contratoComCiclo(31)->vencimentoPara(Carbon::parse('2000-02-10'));

        $this->assertSame('2000-02-29', $vencimento->format('Y-m-d'));
    }

    public function test_o_vencimento_nao_transborda_para_o_mes_seguinte(): void
    {
        foreach (range(1, 12) as $mes) {
            $referencia = Carbon::create(2027, $mes, 1);

            $vencimento = $this->contratoComCiclo(31)->vencimentoPara($referencia);

            $this->assertSame(
                $mes,
                $vencimento->month,
                "Ciclo 31 escapou do mês {$mes} para {$vencimento->format('Y-m-d')}."
            );
        }
    }

    public function test_proximo_vencimento_usa_o_mes_corrente_quando_ainda_nao_passou(): void
    {
        $contrato = $this->contratoComCiclo(20);

        $proximo = $contrato->proximoVencimento(Carbon::parse('2027-03-10'));

        $this->assertSame('2027-03-20', $proximo->format('Y-m-d'));
    }

    public function test_proximo_vencimento_usa_o_mes_seguinte_quando_o_dia_ja_passou(): void
    {
        $contrato = $this->contratoComCiclo(5);

        $proximo = $contrato->proximoVencimento(Carbon::parse('2027-03-10'));

        $this->assertSame('2027-04-05', $proximo->format('Y-m-d'));
    }

    public function test_proximo_vencimento_no_proprio_dia_do_vencimento(): void
    {
        $contrato = $this->contratoComCiclo(10);

        $proximo = $contrato->proximoVencimento(Carbon::parse('2027-03-10'));

        $this->assertSame('2027-03-10', $proximo->format('Y-m-d'));
    }

    public function test_proximo_vencimento_de_janeiro_31_cai_no_ultimo_dia_de_fevereiro(): void
    {
        $contrato = $this->contratoComCiclo(31);

        $proximo = $contrato->proximoVencimento(Carbon::parse('2027-02-01'));

        $this->assertSame('2027-02-28', $proximo->format('Y-m-d'));
    }

    public function test_proximo_vencimento_apos_o_ultimo_dia_de_fevereiro_vai_para_marco(): void
    {
        $contrato = $this->contratoComCiclo(31);

        $proximo = $contrato->proximoVencimento(Carbon::parse('2027-03-01'));

        $this->assertSame('2027-03-31', $proximo->format('Y-m-d'));
    }
}
