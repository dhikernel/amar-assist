<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_autentica_com_credenciais_validas_e_devolve_token(): void
    {
        User::factory()->create([
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'tipo',
                'expira_em',
                'usuario' => ['id', 'nome', 'email', 'criado_em'],
            ])
            ->assertJsonPath('tipo', 'Bearer')
            ->assertJsonPath('usuario.email', 'operador@amarassist.com.br');

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_token_emitido_da_acesso_a_rota_protegida(): void
    {
        User::factory()->create([
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email', 'operador@amarassist.com.br');
    }

    public function test_recusa_senha_incorreta(): void
    {
        User::factory()->create([
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ]);

        $this->postJson('/api/login', [
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-errada',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_mensagem_de_erro_e_identica_para_email_existente_e_inexistente(): void
    {
        User::factory()->create([
            'email' => 'existe@amarassist.com.br',
            'password' => 'senha-correta',
        ]);

        $comEmailExistente = $this->postJson('/api/login', [
            'email' => 'existe@amarassist.com.br',
            'password' => 'senha-errada',
        ]);

        Cache::flush();

        $comEmailInexistente = $this->postJson('/api/login', [
            'email' => 'naoexiste@amarassist.com.br',
            'password' => 'senha-errada',
        ]);

        $comEmailExistente->assertStatus(422);
        $comEmailInexistente->assertStatus(422);

        $this->assertSame(
            $comEmailExistente->json('errors.email'),
            $comEmailInexistente->json('errors.email'),
            'A resposta revela se o e-mail existe na base.'
        );
    }

    public function test_bloqueia_apos_cinco_tentativas_malsucedidas(): void
    {
        User::factory()->create([
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ]);

        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $this->postJson('/api/login', [
                'email' => 'operador@amarassist.com.br',
                'password' => 'senha-errada',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ])->assertStatus(429);
    }

    public function test_login_bem_sucedido_zera_o_contador_de_tentativas(): void
    {
        User::factory()->create([
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ]);

        foreach (range(1, 4) as $ignorado) {
            $this->postJson('/api/login', [
                'email' => 'operador@amarassist.com.br',
                'password' => 'senha-errada',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ])->assertOk();

        $this->postJson('/api/login', [
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-errada',
        ])->assertStatus(422);
    }

    public function test_email_e_tratado_sem_diferenciar_maiusculas(): void
    {
        User::factory()->create([
            'email' => 'operador@amarassist.com.br',
            'password' => 'senha-correta',
        ]);

        $this->postJson('/api/login', [
            'email' => '  OPERADOR@AmarAssist.com.BR  ',
            'password' => 'senha-correta',
        ])->assertOk();
    }

    public function test_exige_email_e_senha(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_recusa_email_com_quebra_de_linha(): void
    {
        $this->postJson('/api/login', [
            'email' => "operador@amarassist.com.br\r\nBcc: invasor@exemplo.com",
            'password' => 'senha-correta',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_senha_nunca_e_gravada_em_texto_puro(): void
    {
        $user = User::factory()->create(['password' => 'senha-em-texto-puro']);

        $this->assertNotSame('senha-em-texto-puro', $user->password);
        $this->assertTrue(Hash::check('senha-em-texto-puro', $user->password));
    }

    public function test_senha_ja_hasheada_nao_e_hasheada_de_novo(): void
    {
        $hash = Hash::make('senha-correta');

        $user = User::factory()->create(['password' => $hash]);

        $this->assertSame($hash, $user->password);
        $this->assertTrue(Hash::check('senha-correta', $user->password));
    }
}
