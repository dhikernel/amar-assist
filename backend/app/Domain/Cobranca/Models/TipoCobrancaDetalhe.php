<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Models;

use App\Domain\Cobranca\Enums\TipoChavePix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoCobrancaDetalhe extends Model
{
    protected $table = 'tipo_cobrancas';

    protected $fillable = [
        'cobranca_id',
        'codigo_barras',
        'linha_digitavel',
        'cartao_bandeira',
        'cartao_titular',
        'cartao_numero',
        'cartao_ultimos_digitos',
        'cartao_validade',
        'pix_tipo_chave',
        'pix_chave',
    ];

    protected $hidden = [
        'cartao_numero',
    ];

    protected $casts = [
        'cartao_numero' => 'encrypted',
        'pix_tipo_chave' => TipoChavePix::class,
    ];

    public function cobranca(): BelongsTo
    {
        return $this->belongsTo(Cobranca::class, 'cobranca_id');
    }
}
