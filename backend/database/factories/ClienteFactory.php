<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Cliente\Enums\SituacaoCliente;
use App\Domain\Cliente\Models\Cliente;
use App\Domain\Shared\Rules\CpfCnpj;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'cpf_cnpj' => CpfCnpj::apenasDigitos(fake()->unique()->cpf()),
            'cep' => CpfCnpj::apenasDigitos(fake()->postcode()),
            'logradouro' => fake()->streetName(),
            'numero' => (string) fake()->buildingNumber(),
            'complemento' => fake()->boolean(30) ? 'Apto '.fake()->numberBetween(1, 200) : null,
            'bairro' => fake()->citySuffix().' '.Str::ucfirst(fake()->word()),
            'cidade' => fake()->city(),
            'uf' => fake()->stateAbbr(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => '11'.fake()->numerify('9########'),
            'situacao' => SituacaoCliente::Ativo->value,
        ];
    }

    public function juridica(): static
    {
        return $this->state(fn (array $attributes) => [
            'nome' => fake()->company(),
            'cpf_cnpj' => CpfCnpj::apenasDigitos(fake()->unique()->cnpj()),
        ]);
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes) => [
            'situacao' => SituacaoCliente::Inativo->value,
        ]);
    }
}
