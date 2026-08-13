<?php

declare(strict_types=1);

namespace App\Domain\Contrato\Repositories;

use App\Domain\Cliente\Models\Cliente;
use App\Domain\Contrato\Models\Contrato;
use App\Domain\Contrato\Resources\ContratoCollection;
use App\Domain\Contrato\Resources\ContratoResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ContratoRepository
{
    protected array $relationships = ['cliente'];

    public function index(): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Contrato::with($this->relationships))
            ->allowedFilters(
                AllowedFilter::partial('numero'),
                AllowedFilter::exact('situacao'),
                AllowedFilter::exact('tipo'),
                AllowedFilter::exact('cliente_id'),
                AllowedFilter::callback('cliente', $this->filtroPorNomeDoCliente()),
            )
            ->allowedSorts('numero', 'data_inicio', 'valor_mensal', 'situacao', 'created_at')
            ->defaultSort('-data_inicio')
            ->paginate(request('per_page', config('settings.AMOUNT_PAGINATE_DEFAULT')))
            ->appends(request()->query());

        return (new ContratoCollection($query))->resource;
    }

    public function getById(string $id): ?ContratoResource
    {
        $contrato = Contrato::with($this->relationships)->find($id);

        return $contrato === null ? null : new ContratoResource($contrato);
    }

    public function store(array $data): ContratoResource
    {
        $this->garantirClienteAtivo((int) $data['cliente_id']);

        DB::beginTransaction();

        try {
            $contrato = Contrato::create($data);
            DB::commit();

            return new ContratoResource($contrato->load($this->relationships));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(array $data, string $id): ContratoResource
    {
        if (isset($data['cliente_id'])) {
            $this->garantirClienteAtivo((int) $data['cliente_id']);
        }

        DB::beginTransaction();

        try {
            $contrato = Contrato::findOrFail($id);
            $contrato->update($data);
            DB::commit();

            return new ContratoResource($contrato->refresh()->load($this->relationships));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(int|string|null $id): void
    {
        DB::beginTransaction();

        try {
            Contrato::findOrFail($id)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function garantirClienteAtivo(int $clienteId): void
    {
        $cliente = Cliente::find($clienteId);

        if ($cliente !== null && ! $cliente->estaAtivo()) {
            throw ValidationException::withMessages([
                'cliente_id' => ['Cliente inativo não pode receber contrato.'],
            ]);
        }
    }

    private function filtroPorNomeDoCliente(): callable
    {
        return function (Builder $query, mixed $valor): void {
            $query->whereHas('cliente', fn (Builder $cliente) => $cliente->where('nome', 'like', '%'.$valor.'%'));
        };
    }
}
