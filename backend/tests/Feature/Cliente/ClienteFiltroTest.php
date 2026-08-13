<?php

declare(strict_types=1);

namespace Tests\Feature\Cliente;

use App\Domain\Auth\Models\User;
use App\Domain\Cliente\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClienteFiltroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_lista_clientes_paginados(): void
    {
        Cliente::factory()->count(3)->create();

        $this->getJson('/api/clientes')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'nome', 'cpf_cnpj', 'situacao', 'endereco', 'contato']],
                'current_page',
                'per_page',
                'total',
                'last_page',
            ]);
    }

    public function test_filtra_por_nome_parcial(): void
    {
        Cliente::factory()->create(['nome' => 'Maria Aparecida']);
        Cliente::factory()->create(['nome' => 'Mariana Costa']);
        Cliente::factory()->create(['nome' => 'Joao Pedro']);

        $resposta = $this->getJson('/api/clientes?filter[nome]=mari')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $nomes = array_column($resposta->json('data'), 'nome');
        sort($nomes);

        $this->assertSame(['Maria Aparecida', 'Mariana Costa'], $nomes);
    }

    public function test_filtra_por_situacao(): void
    {
        Cliente::factory()->count(2)->create();
        Cliente::factory()->inativo()->create(['nome' => 'Cliente Inativo']);

        $this->getJson('/api/clientes?filter[situacao]=inativo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Cliente Inativo');

        $this->getJson('/api/clientes?filter[situacao]=ativo')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filtra_por_cpf_cnpj(): void
    {
        Cliente::factory()->create(['cpf_cnpj' => '11144477735', 'nome' => 'Procurado']);
        Cliente::factory()->create(['cpf_cnpj' => '52998224725']);

        $this->getJson('/api/clientes?filter[cpf_cnpj]=11144477735')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Procurado');
    }

    public function test_filtra_por_cpf_cnpj_com_mascara(): void
    {
        Cliente::factory()->create(['cpf_cnpj' => '11144477735', 'nome' => 'Procurado']);
        Cliente::factory()->create(['cpf_cnpj' => '52998224725']);

        $this->getJson('/api/clientes?filter[cpf_cnpj]=111.444.777-35')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Procurado');
    }

    public function test_filtra_por_inicio_do_documento(): void
    {
        Cliente::factory()->create(['cpf_cnpj' => '11144477735']);
        Cliente::factory()->create(['cpf_cnpj' => '52998224725']);

        $this->getJson('/api/clientes?filter[cpf_cnpj]=111')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_combina_filtros_de_nome_e_situacao(): void
    {
        Cliente::factory()->create(['nome' => 'Maria Ativa']);
        Cliente::factory()->inativo()->create(['nome' => 'Maria Inativa']);
        Cliente::factory()->inativo()->create(['nome' => 'Joao Inativo']);

        $this->getJson('/api/clientes?filter[nome]=maria&filter[situacao]=inativo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Maria Inativa');
    }

    public function test_ordena_por_nome_como_padrao(): void
    {
        Cliente::factory()->create(['nome' => 'Zulmira']);
        Cliente::factory()->create(['nome' => 'Ana']);
        Cliente::factory()->create(['nome' => 'Marcos']);

        $nomes = array_column($this->getJson('/api/clientes')->json('data'), 'nome');

        $this->assertSame(['Ana', 'Marcos', 'Zulmira'], $nomes);
    }

    public function test_aceita_ordenacao_decrescente_por_nome(): void
    {
        Cliente::factory()->create(['nome' => 'Ana']);
        Cliente::factory()->create(['nome' => 'Zulmira']);

        $nomes = array_column($this->getJson('/api/clientes?sort=-nome')->json('data'), 'nome');

        $this->assertSame(['Zulmira', 'Ana'], $nomes);
    }

    public function test_recusa_filtro_nao_permitido(): void
    {
        Cliente::factory()->create(['cidade' => 'Campinas']);
        Cliente::factory()->create(['cidade' => 'Santos']);

        $this->getJson('/api/clientes?filter[cidade]=Campinas')
            ->assertStatus(400);
    }

    public function test_respeita_o_per_page_informado(): void
    {
        Cliente::factory()->count(5)->create();

        $this->getJson('/api/clientes?per_page=2')
            ->assertOk()
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 5)
            ->assertJsonCount(2, 'data');
    }

    public function test_usa_a_paginacao_padrao_do_settings(): void
    {
        Cliente::factory()->count(3)->create();

        $this->getJson('/api/clientes')
            ->assertOk()
            ->assertJsonPath('per_page', (int) config('settings.AMOUNT_PAGINATE_DEFAULT'));
    }

    public function test_cliente_removido_nao_aparece_na_listagem(): void
    {
        Cliente::factory()->count(2)->create();
        Cliente::factory()->create(['nome' => 'Removido'])->delete();

        $this->getJson('/api/clientes')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
