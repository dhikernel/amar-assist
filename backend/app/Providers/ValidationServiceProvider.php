<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Cliente\Enums\SituacaoCliente;
use App\Domain\Shared\Enums\UnidadeFederativa;
use App\Domain\Shared\Rules\CpfCnpj;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Validator::extend('cpf_cnpj', function ($attribute, $value) {
            return (new CpfCnpj)->passes($attribute, $value);
        });

        Validator::extend('cpf_cnpj_unico', function ($attribute, $value, $parameters) {
            $digitos = CpfCnpj::apenasDigitos((string) $value);
            $tabela = $parameters[0] ?? null;
            $coluna = $parameters[1] ?? 'cpf_cnpj';

            if ($tabela === null || $digitos === '') {
                return true;
            }

            $consulta = DB::table($tabela)->where($coluna, $digitos);

            if (isset($parameters[2])) {
                $consulta->where('id', '!=', $parameters[2]);
            }

            return ! $consulta->exists();
        });

        Validator::extend('uf', function ($attribute, $value) {
            return UnidadeFederativa::tryFrom((string) $value) !== null;
        });

        Validator::extend('situacao_cliente', function ($attribute, $value) {
            return SituacaoCliente::tryFrom((string) $value) !== null;
        });
    }
}
