<?php

declare(strict_types=1);

namespace App\Domain\Cliente\Repositories;

use App\Domain\Cliente\Models\Cliente;
use App\Domain\Cliente\Resources\ClienteCollection;
use App\Domain\Cliente\Resources\ClienteResource;
use App\Domain\Shared\Rules\CpfCnpj;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClienteRepository
{
    public function index(): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Cliente::query())
            ->allowedFilters(
                AllowedFilter::partial('nome'),
                AllowedFilter::exact('situacao'),
                AllowedFilter::callback('cpf_cnpj', $this->filtroPorDocumento()),
            )
            ->allowedSorts('nome', 'situacao', 'created_at')
            ->defaultSort('nome')
            ->paginate(request('per_page', config('settings.AMOUNT_PAGINATE_DEFAULT')))
            ->appends(request()->query());

        return (new ClienteCollection($query))->resource;
    }

    public function getById(string $id): ?ClienteResource
    {
        $cliente = Cliente::find($id);

        return $cliente === null ? null : new ClienteResource($cliente);
    }

    public function store(array $data): ClienteResource
    {
        DB::beginTransaction();

        try {
            $cliente = Cliente::create($data);
            DB::commit();

            return new ClienteResource($cliente);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(array $data, string $id): ClienteResource
    {
        DB::beginTransaction();

        try {
            $cliente = Cliente::findOrFail($id);
            $cliente->update($data);
            DB::commit();

            return new ClienteResource($cliente->refresh());
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(int|string|null $id): void
    {
        DB::beginTransaction();

        try {
            Cliente::findOrFail($id)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function filtroPorDocumento(): callable
    {
        return function (Builder $query, mixed $valor): void {
            $digitos = CpfCnpj::apenasDigitos((string) $valor);

            if ($digitos === '') {
                return;
            }

            $query->where('cpf_cnpj', 'like', $digitos.'%');
        };
    }
}
