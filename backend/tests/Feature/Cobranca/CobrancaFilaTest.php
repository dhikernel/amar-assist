<?php

declare(strict_types=1);

namespace Tests\Feature\Cobranca;

use App\Domain\Auth\Models\User;
use App\Domain\Cobranca\Jobs\GerarCobrancaDoContrato;
use App\Domain\Cobranca\Models\Cobranca;
use App\Domain\Contrato\Enums\SituacaoContrato;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CobrancaFilaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_disparo_em_lote_responde_202_e_enfileira_um_job_por_contrato_ativo(): void
    {
        Bus::fake();

        Contrato::factory()->count(3)->create();
        Contrato::factory()->encerrado()->create();

        $this->postJson('/api/cobrancas/gerar-lote', [
            'competencia' => '2027-09',
            'tipo' => 'boleto',
        ])->assertStatus(202)
            ->assertJsonPath('contratos_enfileirados', 3);

        Bus::assertBatched(fn ($lote) => $lote->jobs->count() === 3);
    }

    public function test_jobs_vao_para_a_fila_dedicada_de_cobrancas(): void
    {
        $job = new GerarCobrancaDoContrato(1, '2027-09', 'boleto');

        $this->assertSame('cobrancas', $job->queue);
    }

    public function test_lote_exige_competencia_e_tipo(): void
    {
        $this->postJson('/api/cobrancas/gerar-lote', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['competencia', 'tipo']);
    }

    public function test_lote_recusa_cartao(): void
    {
        $this->postJson('/api/cobrancas/gerar-lote', [
            'competencia' => '2027-09',
            'tipo' => 'cartao',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('tipo');
    }

    public function test_job_gera_a_cobranca_do_contrato(): void
    {
        $contrato = Contrato::factory()->create(['ciclo' => 31]);

        (new GerarCobrancaDoContrato($contrato->id, '2027-02', 'boleto'))
            ->handle(app(\App\Domain\Cobranca\Repositories\CobrancaRepository::class));

        $cobranca = Cobranca::where('contrato_id', $contrato->id)->firstOrFail();

        $this->assertSame('2027-02-28', $cobranca->data_vencimento->format('Y-m-d'));
        $this->assertSame('boleto', $cobranca->tipo->value);
        $this->assertNotNull($cobranca->detalhe->codigo_barras);
    }

    public function test_job_repetido_nao_duplica_a_cobranca(): void
    {
        $contrato = Contrato::factory()->create();
        $repositorio = app(\App\Domain\Cobranca\Repositories\CobrancaRepository::class);

        foreach (range(1, 3) as $ignorado) {
            (new GerarCobrancaDoContrato($contrato->id, '2027-09', 'boleto'))->handle($repositorio);
        }

        $this->assertSame(1, Cobranca::where('contrato_id', $contrato->id)->count());
    }

    public function test_job_ignora_contrato_encerrado(): void
    {
        $contrato = Contrato::factory()->encerrado()->create();

        (new GerarCobrancaDoContrato($contrato->id, '2027-09', 'boleto'))
            ->handle(app(\App\Domain\Cobranca\Repositories\CobrancaRepository::class));

        $this->assertSame(0, Cobranca::where('contrato_id', $contrato->id)->count());
    }

    public function test_job_ignora_contrato_suspenso(): void
    {
        $contrato = Contrato::factory()->create(['situacao' => SituacaoContrato::Suspenso->value]);

        (new GerarCobrancaDoContrato($contrato->id, '2027-09', 'boleto'))
            ->handle(app(\App\Domain\Cobranca\Repositories\CobrancaRepository::class));

        $this->assertSame(0, Cobranca::where('contrato_id', $contrato->id)->count());
    }

    public function test_job_ignora_contrato_inexistente(): void
    {
        (new GerarCobrancaDoContrato(9999, '2027-09', 'boleto'))
            ->handle(app(\App\Domain\Cobranca\Repositories\CobrancaRepository::class));

        $this->assertSame(0, Cobranca::count());
    }

    public function test_lote_exige_autenticacao(): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer invalido')
            ->postJson('/api/cobrancas/gerar-lote', ['competencia' => '2027-09', 'tipo' => 'boleto'])
            ->assertStatus(401);
    }
}
