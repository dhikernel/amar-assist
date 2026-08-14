<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Jobs;

use App\Domain\Cobranca\Enums\TipoCobranca;
use App\Domain\Cobranca\Repositories\CobrancaRepository;
use App\Domain\Contrato\Models\Contrato;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Validation\ValidationException;

class GerarCobrancaDoContrato implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly int $contratoId,
        public readonly string $competencia,
        public readonly string $tipo,
    ) {
        $this->onQueue('cobrancas');
    }

    public function handle(CobrancaRepository $repository): void
    {
        $contrato = Contrato::find($this->contratoId);

        if ($contrato === null || ! $contrato->estaAtivo()) {
            return;
        }

        try {
            $repository->store([
                'contrato_id' => $contrato->id,
                'competencia' => $this->competencia,
                'tipo' => $this->tipo,
                ...$this->detalhe($contrato),
            ]);
        } catch (ValidationException $excecao) {
            if (! array_key_exists('competencia', $excecao->errors())) {
                throw $excecao;
            }
        }
    }

    public function uniqueId(): string
    {
        return $this->contratoId.'-'.$this->competencia;
    }

    private function detalhe(Contrato $contrato): array
    {
        return match (TipoCobranca::from($this->tipo)) {
            TipoCobranca::Boleto => [
                'codigo_barras' => $this->codigoDeBarras($contrato),
            ],
            TipoCobranca::Pix => [
                'pix_tipo_chave' => 'cnpj',
                'pix_chave' => (string) config('settings.COBRANCA_PIX_CHAVE'),
            ],
            TipoCobranca::Cartao => throw ValidationException::withMessages([
                'tipo' => ['Cartão exige dados por cliente e não pode ser gerado em lote.'],
            ]),
        };
    }

    private function codigoDeBarras(Contrato $contrato): string
    {
        return str_pad(
            $contrato->id.str_replace('-', '', $this->competencia),
            44,
            '0',
            STR_PAD_LEFT
        );
    }
}
