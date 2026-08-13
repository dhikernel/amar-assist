<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Models;

use App\Domain\Cobranca\Enums\SituacaoCobranca;
use App\Domain\Cobranca\Enums\TipoCobranca;
use App\Domain\Contrato\Models\Contrato;
use Carbon\CarbonInterface;
use Database\Factories\CobrancaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Cobranca extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cobrancas';

    protected $fillable = [
        'contrato_id',
        'competencia',
        'tipo',
        'data_vencimento',
        'valor_original',
    ];

    protected $casts = [
        'tipo' => TipoCobranca::class,
        'situacao' => SituacaoCobranca::class,
        'competencia' => 'date',
        'data_vencimento' => 'date',
        'data_pagamento' => 'datetime',
        'valor_original' => 'decimal:2',
        'valor_multa' => 'decimal:2',
        'valor_juros' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'dias_atraso' => 'integer',
    ];

    protected $attributes = [
        'situacao' => 'aberta',
        'valor_multa' => 0,
        'valor_juros' => 0,
        'dias_atraso' => 0,
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function detalhe(): HasOne
    {
        return $this->hasOne(TipoCobrancaDetalhe::class, 'cobranca_id');
    }

    public function estaPaga(): bool
    {
        return $this->situacao === SituacaoCobranca::Paga;
    }

    public function diasEmAtraso(?CarbonInterface $referencia = null): int
    {
        if ($this->estaPaga()) {
            return 0;
        }

        $hoje = Carbon::parse($referencia ?? now())->startOfDay();
        $vencimento = Carbon::parse($this->data_vencimento)->startOfDay();

        return $hoje->greaterThan($vencimento) ? $vencimento->diffInDays($hoje) : 0;
    }

    public function estaEmAtraso(?CarbonInterface $referencia = null): bool
    {
        return $this->diasEmAtraso($referencia) > 0;
    }

    public function calcularAcrescimos(?CarbonInterface $referencia = null): array
    {
        $original = number_format((float) $this->valor_original, 2, '.', '');
        $dias = $this->diasEmAtraso($referencia);

        if ($dias === 0) {
            return [
                'dias_atraso' => 0,
                'valor_multa' => '0.00',
                'valor_juros' => '0.00',
                'valor_total' => $original,
            ];
        }

        $multa = bcmul($original, bcdiv((string) config('settings.COBRANCA_MULTA_PERCENTUAL'), '100', 6), 2);
        $percentualDeJuros = bcmul(bcdiv((string) config('settings.COBRANCA_JUROS_DIARIO'), '100', 8), (string) $dias, 8);
        $juros = bcmul($original, $percentualDeJuros, 2);

        return [
            'dias_atraso' => $dias,
            'valor_multa' => $multa,
            'valor_juros' => $juros,
            'valor_total' => bcadd(bcadd($original, $multa, 2), $juros, 2),
        ];
    }

    public function aplicarAcrescimos(?CarbonInterface $referencia = null): static
    {
        foreach ($this->calcularAcrescimos($referencia) as $campo => $valor) {
            $this->{$campo} = $valor;
        }

        return $this;
    }

    protected static function newFactory(): CobrancaFactory
    {
        return CobrancaFactory::new();
    }
}
