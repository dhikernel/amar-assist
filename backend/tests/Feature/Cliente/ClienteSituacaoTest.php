<?php

declare(strict_types=1);

namespace Tests\Feature\Cliente;

use App\Domain\Auth\Models\User;
use App\Domain\Cliente\Enums\SituacaoCliente;
use App\Domain\Cliente\Models\Cliente;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClienteSituacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_desativa_cliente_sem_contrato(): void
    {
        $cliente = Cliente::factory()->create();

        $this->patchJson("/api/clientes/{$cliente->id}/inactive")
            ->assertOk()
            ->assertJsonPath('situacao', 'inativo');

        $this->assertSame(SituacaoCliente::Inativo, $cliente->refresh()->situacao);
    }

    public function test_nao_desativa_cliente_com_contrato(): void
    {
        $cliente = Cliente::factory()->create();
        Contrato::factory()->create(['cliente_id' => $cliente->id]);

        $this->patchJson("/api/clientes/{$cliente->id}/inactive")
            ->assertStatus(422)
            ->assertJsonValidationErrors('situacao');

        $this->assertSame(SituacaoCliente::Ativo, $cliente->refresh()->situacao);
    }

    public function test_contrato_encerrado_tambem_impede_a_desativacao(): void
    {
        $cliente = Cliente::factory()->create();
        Contrato::factory()->encerrado()->create(['cliente_id' => $cliente->id]);

        $this->patchJson("/api/clientes/{$cliente->id}/inactive")
            ->assertStatus(422)
            ->assertJsonValidationErrors('situacao');
    }

    public function test_reativa_cliente(): void
    {
        $cliente = Cliente::factory()->inativo()->create();

        $this->patchJson("/api/clientes/{$cliente->id}/active")
            ->assertOk()
            ->assertJsonPath('situacao', 'ativo');

        $this->assertSame(SituacaoCliente::Ativo, $cliente->refresh()->situacao);
    }

    public function test_reativacao_nao_depende_de_contrato(): void
    {
        $cliente = Cliente::factory()->inativo()->create();
        Contrato::factory()->create(['cliente_id' => $cliente->id]);

        $this->patchJson("/api/clientes/{$cliente->id}/active")
            ->assertOk()
            ->assertJsonPath('situacao', 'ativo');
    }

    public function test_check_delete_informa_ausencia_de_vinculo(): void
    {
        $cliente = Cliente::factory()->create();

        $this->getJson("/api/clientes/{$cliente->id}/check-delete")
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('haveRelationship', false);
    }

    public function test_check_delete_conta_os_contratos_vinculados(): void
    {
        $cliente = Cliente::factory()->create();
        Contrato::factory()->count(3)->create(['cliente_id' => $cliente->id]);

        $this->getJson("/api/clientes/{$cliente->id}/check-delete")
            ->assertOk()
            ->assertJsonPath('count', 3)
            ->assertJsonPath('haveRelationship', true);
    }

    public function test_check_delete_ignora_contrato_de_outro_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        Contrato::factory()->create();

        $this->getJson("/api/clientes/{$cliente->id}/check-delete")
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_update_nao_altera_a_situacao(): void
    {
        $cliente = Cliente::factory()->create();
        Contrato::factory()->create(['cliente_id' => $cliente->id]);

        $this->putJson("/api/clientes/{$cliente->id}", [
            'nome' => 'Nome Alterado',
            'situacao' => 'inativo',
        ])->assertOk();

        $this->assertSame(SituacaoCliente::Ativo, $cliente->refresh()->situacao);
    }

    public function test_cadastro_nao_aceita_situacao_do_cliente(): void
    {
        $this->postJson('/api/clientes', [
            'nome' => 'Joana Ribeiro',
            'cpf_cnpj' => '11144477735',
            'cep' => '01310100',
            'logradouro' => 'Avenida Paulista',
            'numero' => '1578',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'telefone' => '11987654321',
            'situacao' => 'inativo',
        ])->assertCreated()
            ->assertJsonPath('situacao', 'ativo');
    }

    public function test_situacao_exige_autenticacao(): void
    {
        $cliente = Cliente::factory()->create();

        $this->app['auth']->forgetGuards();

        $semToken = $this->withHeader('Authorization', 'Bearer invalido');

        $semToken->patchJson("/api/clientes/{$cliente->id}/inactive")->assertStatus(401);
        $semToken->patchJson("/api/clientes/{$cliente->id}/active")->assertStatus(401);
        $semToken->getJson("/api/clientes/{$cliente->id}/check-delete")->assertStatus(401);
    }

    public function test_cliente_inexistente_retorna_404(): void
    {
        $this->patchJson('/api/clientes/9999/inactive')->assertStatus(404);
        $this->getJson('/api/clientes/9999/check-delete')->assertStatus(404);
    }
}
