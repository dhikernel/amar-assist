<?php

declare(strict_types=1);

namespace Tests\Feature\Cobranca;

use App\Domain\Auth\Models\User;
use App\Domain\Cobranca\Enums\SituacaoCobranca;
use App\Domain\Cobranca\Models\Cobranca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CobrancaPagamentoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_paga_cobranca_em_dia(): void
    {
        $cobranca = Cobranca::factory()->aVencer(5)->create(['valor_original' => 100.00]);

        $this->patchJson("/api/cobrancas/{$cobranca->id}/pagar")
            ->assertOk()
            ->assertJsonPath('situacao', 'paga')
            ->assertJsonPath('valor_total', '100.00');

        $this->assertSame(SituacaoCobranca::Paga, $cobranca->refresh()->situacao);
        $this->assertNotNull($cobranca->data_pagamento);
    }

    public function test_pagamento_congela_os_acrescimos_do_dia(): void
    {
        $cobranca = Cobranca::factory()->emAtraso(10)->create(['valor_original' => 100.00]);

        $this->patchJson("/api/cobrancas/{$cobranca->id}/pagar")
            ->assertOk()
            ->assertJsonPath('dias_atraso', 10)
            ->assertJsonPath('valor_juros', '10.00')
            ->assertJsonPath('valor_total', '112.00');

        $cobranca->refresh();

        $this->assertSame('10.00', $cobranca->valor_juros);
        $this->assertSame('112.00', $cobranca->valor_total);
        $this->assertSame(10, $cobranca->dias_atraso);
    }

    public function test_valores_congelados_nao_crescem_depois_do_pagamento(): void
    {
        $cobranca = Cobranca::factory()->emAtraso(10)->create(['valor_original' => 100.00]);

        $this->patchJson("/api/cobrancas/{$cobranca->id}/pagar")->assertOk();

        $this->travel(30)->days();

        $this->getJson("/api/cobrancas/{$cobranca->id}")
            ->assertOk()
            ->assertJsonPath('dias_atraso', 10)
            ->assertJsonPath('valor_juros', '10.00')
            ->assertJsonPath('valor_total', '112.00');
    }

    public function test_cobranca_ja_paga_nao_pode_ser_paga_de_novo(): void
    {
        $cobranca = Cobranca::factory()->paga()->create();

        $this->patchJson("/api/cobrancas/{$cobranca->id}/pagar")
            ->assertStatus(422)
            ->assertJsonValidationErrors('situacao');
    }

    public function test_cobranca_inexistente_retorna_404(): void
    {
        $this->patchJson('/api/cobrancas/9999/pagar')->assertStatus(404);
    }

    public function test_juros_crescem_a_cada_dia_enquanto_aberta(): void
    {
        $cobranca = Cobranca::factory()->emAtraso(1)->create(['valor_original' => 100.00]);

        $this->getJson("/api/cobrancas/{$cobranca->id}")
            ->assertOk()
            ->assertJsonPath('valor_juros', '1.00');

        $this->travel(4)->days();

        $this->getJson("/api/cobrancas/{$cobranca->id}")
            ->assertOk()
            ->assertJsonPath('dias_atraso', 5)
            ->assertJsonPath('valor_juros', '5.00')
            ->assertJsonPath('valor_total', '107.00');
    }

    public function test_pagamento_exige_autenticacao(): void
    {
        $cobranca = Cobranca::factory()->create();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer invalido')
            ->patchJson("/api/cobrancas/{$cobranca->id}/pagar")
            ->assertStatus(401);
    }
}
