<?php

declare(strict_types=1);

namespace Tests\Feature\Contrato;

use App\Domain\Auth\Models\User;
use App\Domain\Cliente\Models\Cliente;
use App\Domain\Contrato\Enums\SituacaoContrato;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContratoFiltroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_lista_contratos_paginados(): void
    {
        Contrato::factory()->count(3)->create();

        $this->getJson('/api/contratos')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'numero', 'tipo', 'cliente', 'ciclo', 'proximo_vencimento', 'situacao']],
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_filtra_por_numero_parcial(): void
    {
        Contrato::factory()->create(['numero' => 'CT-2027-001']);
        Contrato::factory()->create(['numero' => 'CT-2027-002']);
        Contrato::factory()->create(['numero' => 'CT-2026-001']);

        $this->getJson('/api/contratos?filter[numero]=2027')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filtra_por_situacao(): void
    {
        Contrato::factory()->count(2)->create();
        Contrato::factory()->encerrado()->create();

        $this->getJson('/api/contratos?filter[situacao]=encerrado')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.situacao', 'encerrado');

        $this->getJson('/api/contratos?filter[situacao]=ativo')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filtra_por_tipo(): void
    {
        Contrato::factory()->create();
        Contrato::factory()->create(['cliente_id' => Cliente::factory()->juridica()]);

        $this->getJson('/api/contratos?filter[tipo]=PJ')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo', 'PJ');
    }

    public function test_filtra_por_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        Contrato::factory()->count(2)->create(['cliente_id' => $cliente->id]);
        Contrato::factory()->create();

        $this->getJson("/api/contratos?filter[cliente_id]={$cliente->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filtra_pelo_nome_do_cliente(): void
    {
        $cliente = Cliente::factory()->create(['nome' => 'Construtora Horizonte']);
        Contrato::factory()->create(['cliente_id' => $cliente->id]);
        Contrato::factory()->create();

        $this->getJson('/api/contratos?filter[cliente]=horizonte')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cliente.nome', 'Construtora Horizonte');
    }

    public function test_combina_filtros_de_situacao_e_tipo(): void
    {
        Contrato::factory()->create(['cliente_id' => Cliente::factory()->juridica()]);
        Contrato::factory()->encerrado()->create(['cliente_id' => Cliente::factory()->juridica()]);
        Contrato::factory()->encerrado()->create();

        $this->getJson('/api/contratos?filter[tipo]=PJ&filter[situacao]=encerrado')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_ordena_por_data_de_inicio_decrescente_como_padrao(): void
    {
        Contrato::factory()->create(['numero' => 'CT-ANTIGO', 'data_inicio' => '2025-01-01']);
        Contrato::factory()->create(['numero' => 'CT-RECENTE', 'data_inicio' => '2027-01-01']);

        $numeros = array_column($this->getJson('/api/contratos')->json('data'), 'numero');

        $this->assertSame(['CT-RECENTE', 'CT-ANTIGO'], $numeros);
    }

    public function test_aceita_ordenacao_por_valor(): void
    {
        Contrato::factory()->create(['numero' => 'CT-CARO', 'valor_mensal' => 900]);
        Contrato::factory()->create(['numero' => 'CT-BARATO', 'valor_mensal' => 100]);

        $numeros = array_column($this->getJson('/api/contratos?sort=valor_mensal')->json('data'), 'numero');

        $this->assertSame(['CT-BARATO', 'CT-CARO'], $numeros);
    }

    public function test_recusa_filtro_nao_permitido(): void
    {
        Contrato::factory()->create();

        $this->getJson('/api/contratos?filter[valor_mensal]=100')
            ->assertStatus(400);
    }

    public function test_contrato_removido_nao_aparece_na_listagem(): void
    {
        Contrato::factory()->count(2)->create();
        Contrato::factory()->create()->delete();

        $this->getJson('/api/contratos')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_listagem_traz_o_vencimento_de_cada_contrato(): void
    {
        Contrato::factory()->create(['ciclo' => 31]);

        $contrato = Contrato::first();

        $this->getJson('/api/contratos')
            ->assertOk()
            ->assertJsonPath('data.0.proximo_vencimento', $contrato->proximoVencimento()->format('Y-m-d'));
    }

    public function test_situacao_invalida_no_filtro_nao_quebra_a_listagem(): void
    {
        Contrato::factory()->create();

        $this->getJson('/api/contratos?filter[situacao]=inexistente')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
