<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Repositories;

use App\Domain\Cobranca\Enums\SituacaoCobranca;
use App\Domain\Cobranca\Enums\TipoCobranca;
use App\Domain\Cobranca\Models\Cobranca;
use App\Domain\Cobranca\Resources\CobrancaCollection;
use App\Domain\Cobranca\Resources\CobrancaResource;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use App\Domain\Cobranca\Jobs\GerarCobrancaDoContrato;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CobrancaRepository
{
    protected array $relationships = ['contrato.cliente', 'detalhe'];

    public function index(): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Cobranca::with($this->relationships))
            ->allowedFilters(
                AllowedFilter::exact('situacao'),
                AllowedFilter::exact('tipo'),
                AllowedFilter::exact('contrato_id'),
                AllowedFilter::callback('cliente', $this->filtroPorNomeDoCliente()),
                AllowedFilter::callback('em_atraso', $this->filtroPorAtraso()),
            )
            ->allowedSorts('data_vencimento', 'valor_total', 'situacao', 'created_at')
            ->orderByRaw("FIELD(situacao, 'aberta', 'paga')")
            ->orderByRaw('CASE WHEN situacao = ? AND data_vencimento < ? THEN 0 ELSE 1 END', [
                SituacaoCobranca::Aberta->value,
                Carbon::today()->toDateString(),
            ])
            ->orderBy('data_vencimento')
            ->paginate(request('per_page', config('settings.AMOUNT_PAGINATE_DEFAULT')))
            ->appends(request()->query());

        return (new CobrancaCollection($query))->resource;
    }

    public function getById(string $id): ?CobrancaResource
    {
        $cobranca = Cobranca::with($this->relationships)->find($id);

        return $cobranca === null ? null : new CobrancaResource($cobranca);
    }

    public function store(array $data): CobrancaResource
    {
        $contrato = $this->contratoAtivo((int) $data['contrato_id']);
        $competencia = Carbon::createFromFormat('Y-m', $data['competencia'])->startOfMonth();

        $this->garantirCompetenciaInedita($contrato, $competencia);

        DB::beginTransaction();

        try {
            $cobranca = new Cobranca([
                'contrato_id' => $contrato->id,
                'competencia' => $competencia->toDateString(),
                'tipo' => $data['tipo'],
                'data_vencimento' => $contrato->vencimentoPara($competencia)->toDateString(),
                'valor_original' => $data['valor_original'] ?? $contrato->valor_mensal,
            ]);

            $cobranca->aplicarAcrescimos()->save();
            $cobranca->detalhe()->create($this->detalheDoTipo($data));

            DB::commit();

            return new CobrancaResource($cobranca->load($this->relationships));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(array $data, string $id): CobrancaResource
    {
        DB::beginTransaction();

        try {
            $cobranca = Cobranca::findOrFail($id);

            $this->garantirQueEstaAberta($cobranca);

            $cobranca->fill(array_intersect_key($data, array_flip(['valor_original', 'data_vencimento'])));
            $cobranca->aplicarAcrescimos()->save();

            DB::commit();

            return new CobrancaResource($cobranca->refresh()->load($this->relationships));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(int|string|null $id): void
    {
        DB::beginTransaction();

        try {
            Cobranca::findOrFail($id)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function pagar(string $id): CobrancaResource
    {
        $cobranca = Cobranca::findOrFail($id);

        $this->garantirQueEstaAberta($cobranca);

        DB::beginTransaction();

        try {
            $cobranca->aplicarAcrescimos();
            $cobranca->situacao = SituacaoCobranca::Paga;
            $cobranca->data_pagamento = now();
            $cobranca->save();

            DB::commit();

            return new CobrancaResource($cobranca->refresh()->load($this->relationships));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function gerarEmLote(string $competencia, string $tipo): int
    {
        $contratos = Contrato::where('situacao', 'ativo')->pluck('id');

        Bus::batch(
            $contratos->map(fn (int $id) => new GerarCobrancaDoContrato($id, $competencia, $tipo))->all()
        )->name('cobrancas '.$competencia)->onQueue('cobrancas')->dispatch();

        return $contratos->count();
    }

    public function resumo(): array
    {
        return Cache::remember(
            Cobranca::CHAVE_RESUMO,
            (int) config('settings.RESUMO_CACHE_SEGUNDOS'),
            fn () => $this->calcularResumo()
        );
    }

    public function esquecerResumo(): void
    {
        Cache::forget(Cobranca::CHAVE_RESUMO);
    }

    private function calcularResumo(): array
    {
        $hoje = Carbon::today()->toDateString();

        $abertas = Cobranca::where('situacao', SituacaoCobranca::Aberta->value);

        return [
            'total_em_aberto' => (clone $abertas)->count(),
            'total_em_atraso' => (clone $abertas)->whereDate('data_vencimento', '<', $hoje)->count(),
            'total_pagas' => Cobranca::where('situacao', SituacaoCobranca::Paga->value)->count(),
            'valor_em_aberto' => (string) ((clone $abertas)->sum('valor_original') ?: '0.00'),
            'valor_recebido' => (string) (Cobranca::where('situacao', SituacaoCobranca::Paga->value)->sum('valor_total') ?: '0.00'),
            'atualizado_em' => now()->toIso8601String(),
        ];
    }

    private function contratoAtivo(int $contratoId): Contrato
    {
        $contrato = Contrato::findOrFail($contratoId);

        if (! $contrato->estaAtivo()) {
            throw ValidationException::withMessages([
                'contrato_id' => ['Somente contrato ativo pode gerar cobrança.'],
            ]);
        }

        return $contrato;
    }

    private function garantirCompetenciaInedita(Contrato $contrato, Carbon $competencia): void
    {
        $existe = Cobranca::where('contrato_id', $contrato->id)
            ->whereDate('competencia', $competencia->toDateString())
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'competencia' => ['Já existe cobrança deste contrato para a competência informada.'],
            ]);
        }
    }

    private function garantirQueEstaAberta(Cobranca $cobranca): void
    {
        if ($cobranca->estaPaga()) {
            throw ValidationException::withMessages([
                'situacao' => ['Cobrança paga não pode ser alterada.'],
            ]);
        }
    }

    private function detalheDoTipo(array $data): array
    {
        return match (TipoCobranca::from($data['tipo'])) {
            TipoCobranca::Boleto => [
                'codigo_barras' => $data['codigo_barras'],
                'linha_digitavel' => $data['linha_digitavel'] ?? null,
            ],
            TipoCobranca::Cartao => [
                'cartao_bandeira' => $data['cartao_bandeira'],
                'cartao_titular' => $data['cartao_titular'],
                'cartao_numero' => $data['cartao_numero'],
                'cartao_ultimos_digitos' => substr(preg_replace('/\D/', '', $data['cartao_numero']), -4),
                'cartao_validade' => $data['cartao_validade'],
            ],
            TipoCobranca::Pix => [
                'pix_tipo_chave' => $data['pix_tipo_chave'],
                'pix_chave' => $data['pix_chave'],
            ],
        };
    }

    private function filtroPorNomeDoCliente(): callable
    {
        return function (Builder $query, mixed $valor): void {
            $query->whereHas(
                'contrato.cliente',
                fn (Builder $cliente) => $cliente->where('nome', 'like', '%'.$valor.'%')
            );
        };
    }

    private function filtroPorAtraso(): callable
    {
        return function (Builder $query, mixed $valor): void {
            if (! filter_var($valor, FILTER_VALIDATE_BOOLEAN)) {
                return;
            }

            $query->where('situacao', SituacaoCobranca::Aberta->value)
                ->whereDate('data_vencimento', '<', Carbon::today()->toDateString());
        };
    }
}
