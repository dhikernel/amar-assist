<?php

declare(strict_types=1);

namespace Tests\Feature\Cliente;

use App\Domain\Auth\Models\User;
use App\Domain\Cliente\Enums\SituacaoCliente;
use App\Domain\Cliente\Enums\TipoPessoa;
use App\Domain\Cliente\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClienteCrudTest extends TestCase
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
            'nome' => 'Joana Ribeiro',
            'cpf_cnpj' => '11144477735',
            'cep' => '01310100',
            'logradouro' => 'Avenida Paulista',
            'numero' => '1578',
            'complemento' => 'Conjunto 12',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'email' => 'joana@exemplo.com.br',
            'telefone' => '11987654321',
        ], $sobrescrever);
    }

    public function test_cadastra_cliente_pessoa_fisica(): void
    {
        $this->postJson('/api/clientes', $this->dadosValidos())
            ->assertCreated()
            ->assertJsonPath('nome', 'Joana Ribeiro')
            ->assertJsonPath('cpf_cnpj', '11144477735')
            ->assertJsonPath('tipo_pessoa', 'PF')
            ->assertJsonPath('situacao', 'ativo');

        $this->assertDatabaseHas('clientes', [
            'cpf_cnpj' => '11144477735',
            'tipo_pessoa' => 'PF',
        ]);
    }

    public function test_cadastra_cliente_pessoa_juridica(): void
    {
        $this->postJson('/api/clientes', $this->dadosValidos([
            'nome' => 'Amar Assist Servicos Ltda',
            'cpf_cnpj' => '11222333000181',
        ]))
            ->assertCreated()
            ->assertJsonPath('tipo_pessoa', 'PJ');
    }

    public function test_tipo_pessoa_e_derivado_do_documento_e_nao_aceito_do_cliente(): void
    {
        $this->postJson('/api/clientes', $this->dadosValidos([
            'cpf_cnpj' => '11222333000181',
            'tipo_pessoa' => 'PF',
        ]))
            ->assertCreated()
            ->assertJsonPath('tipo_pessoa', 'PJ');
    }

    public function test_aceita_documento_com_mascara_e_grava_apenas_digitos(): void
    {
        $this->postJson('/api/clientes', $this->dadosValidos([
            'cpf_cnpj' => '111.444.777-35',
            'cep' => '01310-100',
            'telefone' => '(11) 98765-4321',
        ]))->assertCreated();

        $this->assertDatabaseHas('clientes', [
            'cpf_cnpj' => '11144477735',
            'cep' => '01310100',
            'telefone' => '11987654321',
        ]);
    }

    public function test_recusa_documento_invalido(): void
    {
        $this->postJson('/api/clientes', $this->dadosValidos(['cpf_cnpj' => '11144477736']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf_cnpj');
    }

    public function test_recusa_documento_duplicado(): void
    {
        Cliente::factory()->create(['cpf_cnpj' => '11144477735']);

        $this->postJson('/api/clientes', $this->dadosValidos())
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf_cnpj');
    }

    public function test_documento_de_cliente_removido_continua_reservado(): void
    {
        Cliente::factory()->create(['cpf_cnpj' => '11144477735'])->delete();

        $this->postJson('/api/clientes', $this->dadosValidos())
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf_cnpj');
    }

    public function test_recusa_uf_inexistente(): void
    {
        $this->postJson('/api/clientes', $this->dadosValidos(['uf' => 'XX']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('uf');
    }

    public function test_exige_campos_obrigatorios(): void
    {
        $this->postJson('/api/clientes', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'nome', 'cpf_cnpj', 'cep', 'logradouro', 'numero', 'bairro', 'cidade', 'uf', 'telefone',
            ]);
    }

    public function test_exibe_cliente_com_documento_e_telefone_formatados(): void
    {
        $cliente = Cliente::factory()->create([
            'cpf_cnpj' => '11144477735',
            'telefone' => '11987654321',
            'cep' => '01310100',
        ]);

        $this->getJson("/api/clientes/{$cliente->id}")
            ->assertOk()
            ->assertJsonPath('cpf_cnpj_formatado', '111.444.777-35')
            ->assertJsonPath('contato.telefone_formatado', '(11) 98765-4321')
            ->assertJsonPath('endereco.cep_formatado', '01310-100');
    }

    public function test_formata_documento_de_pessoa_juridica(): void
    {
        $cliente = Cliente::factory()->juridica()->create(['cpf_cnpj' => '11222333000181']);

        $this->getJson("/api/clientes/{$cliente->id}")
            ->assertOk()
            ->assertJsonPath('cpf_cnpj_formatado', '11.222.333/0001-81');
    }

    public function test_atualiza_cliente(): void
    {
        $cliente = Cliente::factory()->create(['nome' => 'Nome Antigo']);

        $this->putJson("/api/clientes/{$cliente->id}", ['nome' => 'Nome Novo'])
            ->assertOk()
            ->assertJsonPath('nome', 'Nome Novo');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nome' => 'Nome Novo',
        ]);
    }

    public function test_atualizacao_nao_conflita_com_o_proprio_documento(): void
    {
        $cliente = Cliente::factory()->create(['cpf_cnpj' => '11144477735']);

        $this->putJson("/api/clientes/{$cliente->id}", [
            'cpf_cnpj' => '11144477735',
            'nome' => 'Outro Nome',
        ])->assertOk();
    }

    public function test_atualizacao_recusa_documento_de_outro_cliente(): void
    {
        Cliente::factory()->create(['cpf_cnpj' => '11144477735']);
        $cliente = Cliente::factory()->create(['cpf_cnpj' => '52998224725']);

        $resposta = $this->putJson("/api/clientes/{$cliente->id}", ['cpf_cnpj' => '11144477735'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf_cnpj');

        $corpo = $resposta->content();

        $this->assertStringNotContainsString('SQLSTATE', $corpo);
        $this->assertStringNotContainsString('Integrity constraint', $corpo);
        $this->assertStringNotContainsString('clientes_cpf_cnpj_unique', $corpo);
    }

    public function test_remove_cliente_sem_apagar_o_registro(): void
    {
        $cliente = Cliente::factory()->create();

        $this->deleteJson('/api/clientes', ['uuid' => $cliente->id])
            ->assertNoContent();

        $this->assertSoftDeleted('clientes', ['id' => $cliente->id]);
    }

    public function test_cliente_removido_nao_aparece_na_consulta(): void
    {
        $cliente = Cliente::factory()->create();
        $cliente->delete();

        $this->getJson("/api/clientes/{$cliente->id}")->assertNotFound();
    }

    public function test_situacao_padrao_e_ativo(): void
    {
        $cliente = Cliente::factory()->create();

        $this->assertSame(SituacaoCliente::Ativo, $cliente->situacao);
        $this->assertTrue($cliente->estaAtivo());
    }

    public function test_enum_de_tipo_pessoa_e_aplicado_no_model(): void
    {
        $cliente = Cliente::factory()->juridica()->create();

        $this->assertSame(TipoPessoa::Juridica, $cliente->tipo_pessoa);
    }

    public function test_rotas_de_cliente_exigem_autenticacao(): void
    {
        $this->app['auth']->forgetGuards();

        $cliente = Cliente::factory()->create();

        $this->postJson('/api/logout');

        $semToken = $this->withHeader('Authorization', 'Bearer invalido');

        $semToken->getJson('/api/clientes')->assertStatus(401);
        $semToken->postJson('/api/clientes', $this->dadosValidos())->assertStatus(401);
        $semToken->getJson("/api/clientes/{$cliente->id}")->assertStatus(401);
        $semToken->putJson("/api/clientes/{$cliente->id}", [])->assertStatus(401);
        $semToken->deleteJson('/api/clientes', ['uuid' => $cliente->id])->assertStatus(401);
    }
}
