<?php

declare(strict_types=1);

namespace Tests\Feature\Cobranca;

use App\Domain\Auth\Models\User;
use App\Domain\Cobranca\Enums\SituacaoCobranca;
use App\Domain\Cobranca\Models\Cobranca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CobrancaResumoCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_resumo_conta_abertas_atrasadas_e_pagas(): void
    {
        Cobranca::factory()->count(2)->create(['data_vencimento' => now()->addDays(10)]);
        Cobranca::factory()->count(3)->emAtraso()->create();
        Cobranca::factory()->paga()->create();

        $this->getJson('/api/cobrancas/resumo')
            ->assertOk()
            ->assertJsonPath('total_em_aberto', 5)
            ->assertJsonPath('total_em_atraso', 3)
            ->assertJsonPath('total_pagas', 1)
            ->assertJsonStructure(['valor_em_aberto', 'valor_recebido', 'atualizado_em']);
    }

    public function test_resumo_fica_guardado_no_cache(): void
    {
        Cobranca::factory()->count(2)->create();

        $this->assertNull(Cache::get(Cobranca::CHAVE_RESUMO));

        $this->getJson('/api/cobrancas/resumo')->assertOk();

        $this->assertNotNull(Cache::get(Cobranca::CHAVE_RESUMO));
    }

    public function test_segunda_consulta_vem_do_cache_sem_tocar_o_banco(): void
    {
        Cobranca::factory()->count(2)->create();

        $primeira = $this->getJson('/api/cobrancas/resumo')->assertOk()->json();

        Cobranca::query()->forceDelete();

        $segunda = $this->getJson('/api/cobrancas/resumo')->assertOk()->json();

        $this->assertSame($primeira, $segunda);
        $this->assertSame(2, $segunda['total_em_aberto']);
    }

    public function test_nova_cobranca_invalida_o_cache(): void
    {
        Cobranca::factory()->create();

        $this->getJson('/api/cobrancas/resumo')->assertOk()->assertJsonPath('total_em_aberto', 1);

        Cobranca::factory()->create();

        $this->getJson('/api/cobrancas/resumo')->assertOk()->assertJsonPath('total_em_aberto', 2);
    }

    public function test_pagamento_invalida_o_cache(): void
    {
        $cobranca = Cobranca::factory()->create();

        $this->getJson('/api/cobrancas/resumo')->assertOk()->assertJsonPath('total_pagas', 0);

        $this->patchJson("/api/cobrancas/{$cobranca->id}/pagar")->assertOk();

        $this->getJson('/api/cobrancas/resumo')
            ->assertOk()
            ->assertJsonPath('total_pagas', 1)
            ->assertJsonPath('total_em_aberto', 0);
    }

    public function test_remocao_invalida_o_cache(): void
    {
        $cobranca = Cobranca::factory()->create();

        $this->getJson('/api/cobrancas/resumo')->assertOk()->assertJsonPath('total_em_aberto', 1);

        $this->deleteJson('/api/cobrancas', ['uuid' => $cobranca->id])->assertNoContent();

        $this->getJson('/api/cobrancas/resumo')->assertOk()->assertJsonPath('total_em_aberto', 0);
    }

    public function test_resumo_exige_autenticacao(): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer invalido')
            ->getJson('/api/cobrancas/resumo')
            ->assertStatus(401);
    }
}
