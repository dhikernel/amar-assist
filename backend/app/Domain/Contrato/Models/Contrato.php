<?php

declare(strict_types=1);

namespace App\Domain\Contrato\Models;

use App\Domain\Cliente\Models\Cliente;
use App\Domain\Contrato\Enums\SituacaoContrato;
use App\Domain\Contrato\Enums\TipoContrato;
use Carbon\CarbonInterface;
use Database\Factories\ContratoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Contrato extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'contratos';

    protected $fillable = [
        'cliente_id',
        'numero',
        'ciclo',
        'valor_mensal',
        'data_inicio',
        'data_fim',
    ];

    protected $casts = [
        'tipo' => TipoContrato::class,
        'situacao' => SituacaoContrato::class,
        'ciclo' => 'integer',
        'valor_mensal' => 'decimal:2',
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    protected $attributes = [
        'situacao' => 'ativo',
    ];

    protected static function booted(): void
    {
        static::saving(function (Contrato $contrato): void {
            if (! $contrato->exists || $contrato->isDirty('cliente_id')) {
                $contrato->tipo = $contrato->cliente()->first()?->tipo_pessoa?->value;
            }
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vencimentoPara(CarbonInterface $mes): Carbon
    {
        $referencia = Carbon::parse($mes)->startOfMonth();

        return $referencia->setDay(min($this->ciclo, $referencia->daysInMonth));
    }

    public function proximoVencimento(?CarbonInterface $referencia = null): Carbon
    {
        $base = Carbon::parse($referencia ?? now())->startOfDay();
        $vencimento = $this->vencimentoPara($base);

        if ($vencimento->greaterThanOrEqualTo($base)) {
            return $vencimento;
        }

        return $this->vencimentoPara($base->copy()->addMonthNoOverflow());
    }

    public function estaAtivo(): bool
    {
        return $this->situacao === SituacaoContrato::Ativo;
    }

    public function estaEncerrado(): bool
    {
        return $this->situacao === SituacaoContrato::Encerrado;
    }

    protected static function newFactory(): ContratoFactory
    {
        return ContratoFactory::new();
    }
}
