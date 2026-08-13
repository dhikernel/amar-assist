<?php

declare(strict_types=1);

namespace Tests\Feature\Contrato;

use App\Domain\Auth\Models\User;
use App\Domain\Contrato\Enums\SituacaoContrato;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContratoSituacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_suspende_contrato_ativo(): void
    {
        $contrato = Contrato::factory()->create();

        $this->patchJson("/api/contratos/{$contrato->id}/suspender")
            ->assertOk()
            ->assertJsonPath('situacao', 'suspenso');

        $this->assertSame(SituacaoContrato::Suspenso, $contrato->refresh()->situacao);
    }

    public function test_reativa_contrato_suspenso(): void
    {
        $contrato = Contrato::factory()->create(['situacao' => SituacaoContrato::Suspenso->value]);

        $this->patchJson("/api/contratos/{$contrato->id}/reativar")
            ->assertOk()
            ->assertJsonPath('situacao', 'ativo');
    }

    public function test_encerra_contrato_e_grava_a_data_fim(): void
    {
        $contrato = Contrato::factory()->create();

        $this->patchJson("/api/contratos/{$contrato->id}/encerrar")
            ->assertOk()
            ->assertJsonPath('situacao', 'encerrado')
            ->assertJsonPath('data_fim', now()->format('Y-m-d'));

        $this->assertSame(SituacaoContrato::Encerrado, $contrato->refresh()->situacao);
    }

    public function test_contrato_encerrado_nao_pode_ser_suspenso(): void
    {
        $contrato = Contrato::factory()->encerrado()->create();

        $this->patchJson("/api/contratos/{$contrato->id}/suspender")
            ->assertStatus(422)
            ->assertJsonValidationErrors('situacao');
    }

    public function test_contrato_encerrado_nao_pode_ser_reativado(): void
    {
        $contrato = Contrato::factory()->encerrado()->create();

        $this->patchJson("/api/contratos/{$contrato->id}/reativar")
            ->assertStatus(422)
            ->assertJsonValidationErrors('situacao');

        $this->assertSame(SituacaoContrato::Encerrado, $contrato->refresh()->situacao);
    }

    public function test_contrato_encerrado_nao_pode_ser_encerrado_de_novo(): void
    {
        $contrato = Contrato::factory()->encerrado()->create();

        $this->patchJson("/api/contratos/{$contrato->id}/encerrar")
            ->assertStatus(422)
            ->assertJsonValidationErrors('situacao');
    }

    public function test_update_nao_altera_a_situacao(): void
    {
        $contrato = Contrato::factory()->create();

        $this->putJson("/api/contratos/{$contrato->id}", [
            'ciclo' => 20,
            'situacao' => 'encerrado',
        ])->assertOk();

        $this->assertSame(SituacaoContrato::Ativo, $contrato->refresh()->situacao);
    }

    public function test_cadastro_nao_aceita_situacao(): void
    {
        $cliente = \App\Domain\Cliente\Models\Cliente::factory()->create();

        $this->postJson('/api/contratos', [
            'cliente_id' => $cliente->id,
            'numero' => 'CT-999',
            'ciclo' => 10,
            'valor_mensal' => 100,
            'data_inicio' => '2027-01-01',
            'situacao' => 'encerrado',
        ])->assertCreated()->assertJsonPath('situacao', 'ativo');
    }

    public function test_acoes_de_situacao_exigem_autenticacao(): void
    {
        $contrato = Contrato::factory()->create();

        $this->app['auth']->forgetGuards();

        $semToken = $this->withHeader('Authorization', 'Bearer invalido');

        $semToken->patchJson("/api/contratos/{$contrato->id}/suspender")->assertStatus(401);
        $semToken->patchJson("/api/contratos/{$contrato->id}/reativar")->assertStatus(401);
        $semToken->patchJson("/api/contratos/{$contrato->id}/encerrar")->assertStatus(401);
    }

    public function test_contrato_inexistente_retorna_404(): void
    {
        $this->patchJson('/api/contratos/9999/suspender')->assertStatus(404);
        $this->patchJson('/api/contratos/9999/encerrar')->assertStatus(404);
    }

    public function test_contrato_encerrado_continua_bloqueando_a_desativacao_do_cliente(): void
    {
        $contrato = Contrato::factory()->create();

        $this->patchJson("/api/contratos/{$contrato->id}/encerrar")->assertOk();

        $this->patchJson("/api/clientes/{$contrato->cliente_id}/inactive")
            ->assertStatus(422)
            ->assertJsonValidationErrors('situacao');
    }
}
