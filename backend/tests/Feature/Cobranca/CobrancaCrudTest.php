<?php

declare(strict_types=1);

namespace Tests\Feature\Cobranca;

use App\Domain\Auth\Models\User;
use App\Domain\Cobranca\Enums\SituacaoCobranca;
use App\Domain\Cobranca\Models\Cobranca;
use App\Domain\Contrato\Enums\SituacaoContrato;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CobrancaCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    private function dadosBoleto(array $sobrescrever = []): array
    {
        return array_merge([
            'contrato_id' => Contrato::factory()->create(['ciclo' => 10])->id,
            'competencia' => Carbon::today()->format('Y-m'),
            'tipo' => 'boleto',
            'codigo_barras' => str_repeat('1', 44),
            'linha_digitavel' => '00190500954014481606906809350314337370000000100',
        ], $sobrescrever);
    }

    public function test_gera_cobranca_de_boleto(): void
    {
        $this->postJson('/api/cobrancas', $this->dadosBoleto())
            ->assertCreated()
            ->assertJsonPath('tipo', 'boleto')
            ->assertJsonPath('situacao', 'aberta')
            ->assertJsonPath('detalhe.codigo_barras', str_repeat('1', 44));
    }

    public function test_vencimento_vem_do_ciclo_do_contrato(): void
    {
        $contrato = Contrato::factory()->create(['ciclo' => 31]);
        $competencia = Carbon::parse('2027-02-01');

        $this->postJson('/api/cobrancas', $this->dadosBoleto([
            'contrato_id' => $contrato->id,
            'competencia' => '2027-02',
        ]))
            ->assertCreated()
            ->assertJsonPath('data_vencimento', '2027-02-28');
    }

    public function test_valor_original_vem_do_contrato_quando_omitido(): void
    {
        $contrato = Contrato::factory()->create(['valor_mensal' => 349.90]);

        $this->postJson('/api/cobrancas', $this->dadosBoleto(['contrato_id' => $contrato->id]))
            ->assertCreated()
            ->assertJsonPath('valor_original', '349.90');
    }

    public function test_gera_cobranca_de_pix(): void
    {
        $this->postJson('/api/cobrancas', $this->dadosBoleto([
            'tipo' => 'pix',
            'codigo_barras' => null,
            'linha_digitavel' => null,
            'pix_tipo_chave' => 'email',
            'pix_chave' => 'financeiro@amarassist.com.br',
        ]))
            ->assertCreated()
            ->assertJsonPath('tipo', 'pix')
            ->assertJsonPath('detalhe.chave', 'financeiro@amarassist.com.br')
            ->assertJsonPath('detalhe.tipo_chave', 'email');
    }

    public function test_gera_cobranca_de_cartao(): void
    {
        $this->postJson('/api/cobrancas', $this->dadosBoleto([
            'tipo' => 'cartao',
            'codigo_barras' => null,
            'linha_digitavel' => null,
            'cartao_bandeira' => 'Visa',
            'cartao_titular' => 'JOANA RIBEIRO',
            'cartao_numero' => '4111111111111111',
            'cartao_validade' => '12/2030',
        ]))
            ->assertCreated()
            ->assertJsonPath('tipo', 'cartao')
            ->assertJsonPath('detalhe.ultimos_digitos', '1111')
            ->assertJsonPath('detalhe.bandeira', 'Visa');
    }

    public function test_numero_do_cartao_e_gravado_criptografado(): void
    {
        $this->postJson('/api/cobrancas', $this->dadosBoleto([
            'tipo' => 'cartao',
            'codigo_barras' => null,
            'cartao_bandeira' => 'Visa',
            'cartao_titular' => 'JOANA RIBEIRO',
            'cartao_numero' => '4111111111111111',
            'cartao_validade' => '12/2030',
        ]))->assertCreated();

        $gravado = \DB::table('tipo_cobrancas')->value('cartao_numero');

        $this->assertNotSame('4111111111111111', $gravado);
        $this->assertStringNotContainsString('4111111111111111', (string) $gravado);
    }

    public function test_resposta_nunca_devolve_o_numero_do_cartao(): void
    {
        $resposta = $this->postJson('/api/cobrancas', $this->dadosBoleto([
            'tipo' => 'cartao',
            'codigo_barras' => null,
            'cartao_bandeira' => 'Visa',
            'cartao_titular' => 'JOANA RIBEIRO',
            'cartao_numero' => '4111111111111111',
            'cartao_validade' => '12/2030',
        ]))->assertCreated();

        $this->assertStringNotContainsString('4111111111111111', $resposta->content());
    }

    public function test_exige_codigo_de_barras_para_boleto(): void
    {
        $this->postJson('/api/cobrancas', $this->dadosBoleto(['codigo_barras' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('codigo_barras');
    }

    public function test_exige_chave_para_pix(): void
    {
        $this->postJson('/api/cobrancas', $this->dadosBoleto([
            'tipo' => 'pix',
            'codigo_barras' => null,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pix_tipo_chave', 'pix_chave']);
    }

    public function test_exige_dados_do_cartao_para_cartao(): void
    {
        $this->postJson('/api/cobrancas', $this->dadosBoleto([
            'tipo' => 'cartao',
            'codigo_barras' => null,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cartao_bandeira', 'cartao_titular', 'cartao_numero', 'cartao_validade']);
    }

    public function test_recusa_competencia_duplicada_no_mesmo_contrato(): void
    {
        $contrato = Contrato::factory()->create();
        $competencia = Carbon::today()->format('Y-m');

        $this->postJson('/api/cobrancas', $this->dadosBoleto([
            'contrato_id' => $contrato->id,
            'competencia' => $competencia,
        ]))->assertCreated();

        $this->postJson('/api/cobrancas', $this->dadosBoleto([
            'contrato_id' => $contrato->id,
            'competencia' => $competencia,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('competencia');
    }

    public function test_recusa_contrato_nao_ativo(): void
    {
        $contrato = Contrato::factory()->create(['situacao' => SituacaoContrato::Suspenso->value]);

        $this->postJson('/api/cobrancas', $this->dadosBoleto(['contrato_id' => $contrato->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('contrato_id');
    }

    public function test_exige_campos_obrigatorios(): void
    {
        $this->postJson('/api/cobrancas', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contrato_id', 'competencia', 'tipo']);
    }

    public function test_recusa_tipo_desconhecido(): void
    {
        $this->postJson('/api/cobrancas', $this->dadosBoleto(['tipo' => 'dinheiro']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tipo');
    }

    public function test_exibe_cobranca_com_contrato_e_cliente(): void
    {
        $cobranca = Cobranca::factory()->create();

        $this->getJson("/api/cobrancas/{$cobranca->id}")
            ->assertOk()
            ->assertJsonPath('contrato.id', $cobranca->contrato_id)
            ->assertJsonStructure(['contrato' => ['id', 'numero', 'cliente' => ['id', 'nome']]]);
    }

    public function test_remove_cobranca_sem_apagar_o_registro(): void
    {
        $cobranca = Cobranca::factory()->create();

        $this->deleteJson('/api/cobrancas', ['uuid' => $cobranca->id])
            ->assertNoContent();

        $this->assertSoftDeleted('cobrancas', ['id' => $cobranca->id]);
    }

    public function test_cobranca_paga_nao_pode_ser_alterada(): void
    {
        $cobranca = Cobranca::factory()->paga()->create();

        $this->putJson("/api/cobrancas/{$cobranca->id}", ['valor_original' => 500])
            ->assertStatus(422)
            ->assertJsonValidationErrors('situacao');
    }

    public function test_rotas_de_cobranca_exigem_autenticacao(): void
    {
        $cobranca = Cobranca::factory()->create();

        $this->app['auth']->forgetGuards();

        $semToken = $this->withHeader('Authorization', 'Bearer invalido');

        $semToken->getJson('/api/cobrancas')->assertStatus(401);
        $semToken->postJson('/api/cobrancas', [])->assertStatus(401);
        $semToken->getJson("/api/cobrancas/{$cobranca->id}")->assertStatus(401);
        $semToken->deleteJson('/api/cobrancas', ['uuid' => $cobranca->id])->assertStatus(401);
    }

    public function test_situacao_padrao_e_aberta(): void
    {
        $cobranca = Cobranca::factory()->create();

        $this->assertSame(SituacaoCobranca::Aberta, $cobranca->situacao);
    }
}
