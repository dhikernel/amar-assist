<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SessaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_rotas_protegidas_exigem_token(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
        $this->postJson('/api/logout')->assertStatus(401);
    }

    public function test_token_invalido_e_recusado(): void
    {
        $this->withHeader('Authorization', 'Bearer token-inventado')
            ->getJson('/api/me')
            ->assertStatus(401);
    }

    public function test_me_devolve_o_usuario_autenticado(): void
    {
        $user = User::factory()->create(['name' => 'Maria Souza']);

        $this->withHeader('Authorization', 'Bearer '.$user->createToken('teste')->plainTextToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('nome', 'Maria Souza');
    }

    public function test_resposta_nunca_expoe_a_senha(): void
    {
        $user = User::factory()->create();

        $resposta = $this->withHeader('Authorization', 'Bearer '.$user->createToken('teste')->plainTextToken)
            ->getJson('/api/me')
            ->assertOk();

        $this->assertArrayNotHasKey('password', $resposta->json());
        $this->assertArrayNotHasKey('remember_token', $resposta->json());
    }

    public function test_logout_revoga_o_token_usado(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('teste')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me')
            ->assertStatus(401);
    }

    public function test_logout_nao_derruba_os_demais_dispositivos(): void
    {
        $user = User::factory()->create();

        $tokenCelular = $user->createToken('celular')->plainTextToken;
        $tokenNotebook = $user->createToken('notebook')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$tokenCelular)
            ->postJson('/api/logout')
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$tokenNotebook)
            ->getJson('/api/me')
            ->assertOk();

        $this->assertSame(1, $user->tokens()->count());
    }
}
