<?php

declare(strict_types=1);

namespace App\Domain\Contrato\Models;

use App\Domain\Cliente\Models\Cliente;
use App\Domain\Contrato\Enums\SituacaoContrato;
use App\Domain\Contrato\Enums\TipoContrato;
use Database\Factories\ContratoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contrato extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'contratos';

    protected $fillable = [
        'cliente_id',
        'numero',
        'tipo',
        'ciclo',
        'valor_mensal',
        'data_inicio',
        'data_fim',
        'situacao',
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

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    protected static function newFactory(): ContratoFactory
    {
        return ContratoFactory::new();
    }
}
