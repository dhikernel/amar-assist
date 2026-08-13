<?php

declare(strict_types=1);

namespace Tests\Feature\Contrato;

use App\Domain\Auth\Models\User;
use App\Domain\Cliente\Models\Cliente;
use App\Domain\Contrato\Enums\TipoContrato;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContratoCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    private function dadosValidos(array $sobrescrever = []): array
    {
        return array_merge([
            'cliente_id' => Cliente::factory()->create()->id,
            'numero' => 'CT-000123',
            'ciclo' => 10,
            'valor_mensal' => 249.90,
            'data_inicio' => '2027-01-05',
        ], $sobrescrever);
    }

    public function test_cadastra_contrato(): void
    {
        $this->postJson('/api/contratos', $this->dadosValidos())
            ->assertCreated()
            ->assertJsonPath('numero', 'CT-000123')
            ->assertJsonPath('ciclo', 10)
            ->assertJsonPath('situacao', 'ativo');

        $this->assertDatabaseHas('contratos', ['numero' => 'CT-000123']);
    }

    public function test_tipo_e_derivado_do_documento_do_cliente(): void
    {
        $pessoaFisica = Cliente::factory()->create();
        $pessoaJuridica = Cliente::factory()->juridica()->create();

        $this->postJson('/api/contratos', $this->dadosValidos([
            'cliente_id' => $pessoaFisica->id,
            'numero' => 'CT-PF-0001',
        ]))->assertCreated()->assertJsonPath('tipo', 'PF');

        $this->postJson('/api/contratos', $this->dadosValidos([
            'cliente_id' => $pessoaJuridica->id,
            'numero' => 'CT-PJ-0001',
        ]))->assertCreated()->assertJsonPath('tipo', 'PJ');
    }

    public function test_tipo_enviado_na_requisicao_e_ignorado(): void
    {
        $pessoaJuridica = Cliente::factory()->juridica()->create();

        $this->postJson('/api/contratos', $this->dadosValidos([
            'cliente_id' => $pessoaJuridica->id,
            'tipo' => 'PF',
        ]))->assertCreated()->assertJsonPath('tipo', 'PJ');
    }

    public function test_tipo_acompanha_a_troca_de_cliente(): void
    {
        $contrato = Contrato::factory()->create();
        $pessoaJuridica = Cliente::factory()->juridica()->create();

        $this->assertSame(TipoContrato::Fisica, $contrato->tipo);

        $this->putJson("/api/contratos/{$contrato->id}", ['cliente_id' => $pessoaJuridica->id])
            ->assertOk()
            ->assertJsonPath('tipo', 'PJ');
    }

    public function test_resposta_traz_o_proximo_vencimento_calculado(): void
    {
        $contrato = Contrato::factory()->create(['ciclo' => 31]);

        $this->getJson("/api/contratos/{$contrato->id}")
            ->assertOk()
            ->assertJsonPath('proximo_vencimento', $contrato->proximoVencimento()->format('Y-m-d'));
    }

    public function test_resposta_traz_os_dados_do_cliente(): void
    {
        $cliente = Cliente::factory()->create(['nome' => 'Joana Ribeiro', 'cpf_cnpj' => '11144477735']);
        $contrato = Contrato::factory()->create(['cliente_id' => $cliente->id]);

        $this->getJson("/api/contratos/{$contrato->id}")
            ->assertOk()
            ->assertJsonPath('cliente.id', $cliente->id)
            ->assertJsonPath('cliente.nome', 'Joana Ribeiro')
            ->assertJsonPath('cliente.cpf_cnpj_formatado', '111.444.777-35');
    }

    public function test_exige_campos_obrigatorios(): void
    {
        $this->postJson('/api/contratos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cliente_id', 'numero', 'ciclo', 'valor_mensal', 'data_inicio']);
    }

    public function test_recusa_cliente_inexistente(): void
    {
        $this->postJson('/api/contratos', $this->dadosValidos(['cliente_id' => 9999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cliente_id');
    }

    public function test_recusa_cliente_inativo(): void
    {
        $cliente = Cliente::factory()->inativo()->create();

        $this->postJson('/api/contratos', $this->dadosValidos(['cliente_id' => $cliente->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cliente_id');
    }

    public function test_recusa_numero_duplicado(): void
    {
        Contrato::factory()->create(['numero' => 'CT-000123']);

        $this->postJson('/api/contratos', $this->dadosValidos())
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero');
    }

    public function test_atualizacao_nao_conflita_com_o_proprio_numero(): void
    {
        $contrato = Contrato::factory()->create(['numero' => 'CT-000123']);

        $this->putJson("/api/contratos/{$contrato->id}", ['numero' => 'CT-000123', 'ciclo' => 20])
            ->assertOk()
            ->assertJsonPath('ciclo', 20);
    }

    /** @dataProvider ciclosInvalidos */
    public function test_recusa_ciclo_fora_do_intervalo(mixed $ciclo): void
    {
        $this->postJson('/api/contratos', $this->dadosValidos(['ciclo' => $ciclo]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('ciclo');
    }

    public static function ciclosInvalidos(): array
    {
        return [
            'zero' => [0],
            'negativo' => [-5],
            'acima de 31' => [32],
            'texto' => ['dez'],
        ];
    }

    public function test_aceita_ciclo_nos_limites(): void
    {
        $this->postJson('/api/contratos', $this->dadosValidos(['ciclo' => 1, 'numero' => 'CT-C1']))
            ->assertCreated();

        $this->postJson('/api/contratos', $this->dadosValidos(['ciclo' => 31, 'numero' => 'CT-C31']))
            ->assertCreated();
    }

    public function test_recusa_data_fim_anterior_ao_inicio(): void
    {
        $this->postJson('/api/contratos', $this->dadosValidos([
            'data_inicio' => '2027-06-01',
            'data_fim' => '2027-05-01',
        ]))->assertStatus(422)->assertJsonValidationErrors('data_fim');
    }

    public function test_recusa_valor_mensal_zerado(): void
    {
        $this->postJson('/api/contratos', $this->dadosValidos(['valor_mensal' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('valor_mensal');
    }

    public function test_remove_contrato_sem_apagar_o_registro(): void
    {
        $contrato = Contrato::factory()->create();

        $this->deleteJson('/api/contratos', ['uuid' => $contrato->id])
            ->assertNoContent();

        $this->assertSoftDeleted('contratos', ['id' => $contrato->id]);
    }

    public function test_contrato_removido_nao_aparece_na_consulta(): void
    {
        $contrato = Contrato::factory()->create();
        $contrato->delete();

        $this->getJson("/api/contratos/{$contrato->id}")->assertNotFound();
    }

    public function test_rotas_de_contrato_exigem_autenticacao(): void
    {
        $contrato = Contrato::factory()->create();

        $this->app['auth']->forgetGuards();

        $semToken = $this->withHeader('Authorization', 'Bearer invalido');

        $semToken->getJson('/api/contratos')->assertStatus(401);
        $semToken->postJson('/api/contratos', [])->assertStatus(401);
        $semToken->getJson("/api/contratos/{$contrato->id}")->assertStatus(401);
        $semToken->putJson("/api/contratos/{$contrato->id}", [])->assertStatus(401);
        $semToken->deleteJson('/api/contratos', ['uuid' => $contrato->id])->assertStatus(401);
    }
}
