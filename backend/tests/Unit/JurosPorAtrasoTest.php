<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Cobranca\Enums\SituacaoCobranca;
use App\Domain\Cobranca\Models\Cobranca;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JurosPorAtrasoTest extends TestCase
{
    private function cobranca(string $valor, string $vencimento): Cobranca
    {
        $cobranca = new Cobranca;
        $cobranca->valor_original = $valor;
        $cobranca->data_vencimento = $vencimento;
        $cobranca->situacao = SituacaoCobranca::Aberta;

        return $cobranca;
    }

    public function test_nao_ha_acrescimo_antes_do_vencimento(): void
    {
        $acrescimos = $this->cobranca('100.00', '2027-03-10')
            ->calcularAcrescimos(Carbon::parse('2027-03-05'));

        $this->assertSame(0, $acrescimos['dias_atraso']);
        $this->assertSame('0.00', $acrescimos['valor_juros']);
        $this->assertSame('0.00', $acrescimos['valor_multa']);
        $this->assertSame('100.00', $acrescimos['valor_total']);
    }

    public function test_nao_ha_acrescimo_no_proprio_dia_do_vencimento(): void
    {
        $acrescimos = $this->cobranca('100.00', '2027-03-10')
            ->calcularAcrescimos(Carbon::parse('2027-03-10'));

        $this->assertSame(0, $acrescimos['dias_atraso']);
        $this->assertSame('100.00', $acrescimos['valor_total']);
    }

    public function test_um_dia_de_atraso_acrescenta_um_por_cento(): void
    {
        $acrescimos = $this->cobranca('100.00', '2027-03-10')
            ->calcularAcrescimos(Carbon::parse('2027-03-11'));

        $this->assertSame(1, $acrescimos['dias_atraso']);
        $this->assertSame('1.00', $acrescimos['valor_juros']);
        $this->assertSame('2.00', $acrescimos['valor_multa']);
        $this->assertSame('103.00', $acrescimos['valor_total']);
    }

    public function test_dez_dias_de_atraso_acrescentam_dez_por_cento(): void
    {
        $acrescimos = $this->cobranca('100.00', '2027-03-10')
            ->calcularAcrescimos(Carbon::parse('2027-03-20'));

        $this->assertSame(10, $acrescimos['dias_atraso']);
        $this->assertSame('10.00', $acrescimos['valor_juros']);
        $this->assertSame('112.00', $acrescimos['valor_total']);
    }

    public function test_juros_sao_simples_e_nao_compostos(): void
    {
        $acrescimos = $this->cobranca('1000.00', '2027-01-01')
            ->calcularAcrescimos(Carbon::parse('2027-02-01'));

        $this->assertSame(31, $acrescimos['dias_atraso']);
        $this->assertSame('310.00', $acrescimos['valor_juros']);
    }

    public function test_atraso_atravessando_a_virada_do_mes(): void
    {
        $acrescimos = $this->cobranca('200.00', '2027-01-28')
            ->calcularAcrescimos(Carbon::parse('2027-02-03'));

        $this->assertSame(6, $acrescimos['dias_atraso']);
        $this->assertSame('12.00', $acrescimos['valor_juros']);
    }

    public function test_atraso_atravessando_a_virada_do_ano(): void
    {
        $acrescimos = $this->cobranca('100.00', '2026-12-30')
            ->calcularAcrescimos(Carbon::parse('2027-01-02'));

        $this->assertSame(3, $acrescimos['dias_atraso']);
        $this->assertSame('3.00', $acrescimos['valor_juros']);
    }

    public function test_cobranca_paga_nao_recebe_acrescimo(): void
    {
        $cobranca = $this->cobranca('100.00', '2027-03-10');
        $cobranca->situacao = SituacaoCobranca::Paga;

        $acrescimos = $cobranca->calcularAcrescimos(Carbon::parse('2027-06-10'));

        $this->assertSame(0, $acrescimos['dias_atraso']);
        $this->assertSame('100.00', $acrescimos['valor_total']);
    }

    public function test_valor_quebrado_arredonda_para_centavos(): void
    {
        $acrescimos = $this->cobranca('249.90', '2027-03-10')
            ->calcularAcrescimos(Carbon::parse('2027-03-13'));

        $this->assertSame(3, $acrescimos['dias_atraso']);
        $this->assertSame('7.49', $acrescimos['valor_juros']);
        $this->assertSame('4.99', $acrescimos['valor_multa']);
        $this->assertSame('262.38', $acrescimos['valor_total']);
    }

    public function test_o_total_e_sempre_a_soma_exata_das_parcelas(): void
    {
        foreach (['0.01', '33.33', '99.99', '1234.56', '9999.99'] as $valor) {
            foreach ([1, 7, 45, 200] as $dias) {
                $acrescimos = $this->cobranca($valor, '2027-01-01')
                    ->calcularAcrescimos(Carbon::parse('2027-01-01')->addDays($dias));

                $soma = bcadd(bcadd($valor, $acrescimos['valor_multa'], 2), $acrescimos['valor_juros'], 2);

                $this->assertSame(
                    $soma,
                    $acrescimos['valor_total'],
                    "Total divergiu para valor {$valor} com {$dias} dias de atraso."
                );
            }
        }
    }

    public function test_aplicar_acrescimos_grava_os_valores_no_modelo(): void
    {
        $cobranca = $this->cobranca('100.00', '2027-03-10')
            ->aplicarAcrescimos(Carbon::parse('2027-03-15'));

        $this->assertSame(5, $cobranca->dias_atraso);
        $this->assertSame('5.00', $cobranca->valor_juros);
        $this->assertSame('107.00', $cobranca->valor_total);
    }
}
