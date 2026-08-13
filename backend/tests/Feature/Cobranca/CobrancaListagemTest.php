<?php

declare(strict_types=1);

namespace Tests\Feature\Cobranca;

use App\Domain\Auth\Models\User;
use App\Domain\Cliente\Models\Cliente;
use App\Domain\Cobranca\Models\Cobranca;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CobrancaListagemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    private function situacoesNaOrdem(): array
    {
        return array_map(
            fn (array $item) => [$item['situacao'], $item['em_atraso']],
            $this->getJson('/api/cobrancas')->json('data')
        );
    }

    public function test_atrasadas_vem_antes_das_a_vencer_e_pagas_por_ultimo(): void
    {
        Cobranca::factory()->paga()->create();
        Cobranca::factory()->aVencer(10)->create();
        Cobranca::factory()->emAtraso(20)->create();

        $this->assertSame(
            [
                ['aberta', true],
                ['aberta', false],
                ['paga', false],
            ],
            $this->situacoesNaOrdem()
        );
    }

    public function test_pagas_ficam_sempre_ao_final_mesmo_vencendo_antes(): void
    {
        Cobranca::factory()->paga()->create(['data_vencimento' => Carbon::today()->subDays(90)->toDateString()]);
        Cobranca::factory()->aVencer(30)->create();

        $situacoes = array_column($this->getJson('/api/cobrancas')->json('data'), 'situacao');

        $this->assertSame(['aberta', 'paga'], $situacoes);
    }

    public function test_entre_atrasadas_a_mais_antiga_vem_primeiro(): void
    {
        $recente = Cobranca::factory()->emAtraso(5)->create();
        $antiga = Cobranca::factory()->emAtraso(60)->create();

        $ids = array_column($this->getJson('/api/cobrancas')->json('data'), 'id');

        $this->assertSame([$antiga->id, $recente->id], $ids);
    }

    public function test_listagem_traz_os_acrescimos_calculados(): void
    {
        Cobranca::factory()->emAtraso(10)->create(['valor_original' => 100.00]);

        $item = $this->getJson('/api/cobrancas')->json('data.0');

        $this->assertSame(10, $item['dias_atraso']);
        $this->assertTrue($item['em_atraso']);
        $this->assertSame('10.00', $item['valor_juros']);
        $this->assertSame('2.00', $item['valor_multa']);
        $this->assertSame('112.00', $item['valor_total']);
    }

    public function test_cobranca_a_vencer_nao_tem_acrescimo(): void
    {
        Cobranca::factory()->aVencer(5)->create(['valor_original' => 100.00]);

        $item = $this->getJson('/api/cobrancas')->json('data.0');

        $this->assertSame(0, $item['dias_atraso']);
        $this->assertFalse($item['em_atraso']);
        $this->assertSame('100.00', $item['valor_total']);
    }

    public function test_filtra_apenas_as_em_atraso(): void
    {
        Cobranca::factory()->emAtraso(10)->create();
        Cobranca::factory()->aVencer(10)->create();
        Cobranca::factory()->paga()->create();

        $this->getJson('/api/cobrancas?filter[em_atraso]=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.em_atraso', true);
    }

    public function test_filtra_por_situacao(): void
    {
        Cobranca::factory()->count(2)->create();
        Cobranca::factory()->paga()->create();

        $this->getJson('/api/cobrancas?filter[situacao]=paga')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filtra_por_tipo(): void
    {
        Cobranca::factory()->create();
        Cobranca::factory()->pix()->create();

        $this->getJson('/api/cobrancas?filter[tipo]=pix')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo', 'pix');
    }

    public function test_filtra_pelo_nome_do_cliente(): void
    {
        $cliente = Cliente::factory()->create(['nome' => 'Construtora Horizonte']);
        $contrato = Contrato::factory()->create(['cliente_id' => $cliente->id]);
        Cobranca::factory()->create(['contrato_id' => $contrato->id]);
        Cobranca::factory()->create();

        $this->getJson('/api/cobrancas?filter[cliente]=horizonte')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_recusa_filtro_nao_permitido(): void
    {
        Cobranca::factory()->create();

        $this->getJson('/api/cobrancas?filter[valor_total]=100')
            ->assertStatus(400);
    }

    public function test_cobranca_removida_nao_aparece_na_listagem(): void
    {
        Cobranca::factory()->count(2)->create();
        Cobranca::factory()->create()->delete();

        $this->getJson('/api/cobrancas')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
