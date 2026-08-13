<?php

declare(strict_types=1);

namespace App\Domain\Cliente\Models;

use App\Domain\Cliente\Enums\SituacaoCliente;
use App\Domain\Cliente\Enums\TipoPessoa;
use App\Domain\Shared\Rules\CpfCnpj;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nome',
        'cpf_cnpj',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'email',
        'telefone',
        'situacao',
    ];

    protected $casts = [
        'tipo_pessoa' => TipoPessoa::class,
        'situacao' => SituacaoCliente::class,
    ];

    protected $attributes = [
        'situacao' => 'ativo',
    ];

    protected function cpfCnpj(): Attribute
    {
        return Attribute::make(
            set: function (string $value): array {
                $digitos = CpfCnpj::apenasDigitos($value);

                return [
                    'cpf_cnpj' => $digitos,
                    'tipo_pessoa' => TipoPessoa::pelaQuantidadeDeDigitos($digitos)?->value,
                ];
            },
        );
    }

    protected function telefone(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => CpfCnpj::apenasDigitos($value),
        );
    }

    protected function cep(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => CpfCnpj::apenasDigitos($value),
        );
    }

    protected function uf(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => mb_strtoupper(trim($value)),
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : mb_strtolower(trim($value)),
        );
    }

    public function getCpfCnpjFormatadoAttribute(): string
    {
        $documento = (string) $this->cpf_cnpj;

        if (strlen($documento) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $documento);
        }

        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento);
    }

    public function getTelefoneFormatadoAttribute(): string
    {
        $telefone = (string) $this->telefone;

        if (strlen($telefone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
        }

        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
    }

    public function getCepFormatadoAttribute(): string
    {
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', (string) $this->cep);
    }

    public function estaAtivo(): bool
    {
        return $this->situacao === SituacaoCliente::Ativo;
    }

    protected static function newFactory(): ClienteFactory
    {
        return ClienteFactory::new();
    }
}
