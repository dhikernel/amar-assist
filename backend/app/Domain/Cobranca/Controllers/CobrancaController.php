<?php

declare(strict_types=1);

namespace App\Domain\Cobranca\Controllers;

use App\Domain\Cobranca\Repositories\CobrancaRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CobrancaController extends Controller
{
    protected CobrancaRepository $repository;

    protected array $validators = [
        'contrato_id' => 'required|integer|exists:contratos,id',
        'competencia' => 'required|date_format:Y-m',
        'tipo' => 'required|in:boleto,cartao,pix',
        'valor_original' => 'nullable|numeric|min:0.01',
        'data_vencimento' => 'nullable|date',

        'codigo_barras' => 'required_if:tipo,boleto|nullable|string|digits:44',
        'linha_digitavel' => 'nullable|string|max:54',

        'cartao_bandeira' => 'required_if:tipo,cartao|nullable|string|max:20',
        'cartao_titular' => 'required_if:tipo,cartao|nullable|string|max:100',
        'cartao_numero' => 'required_if:tipo,cartao|nullable|string|digits_between:13,19',
        'cartao_validade' => 'required_if:tipo,cartao|nullable|date_format:m/Y',

        'pix_tipo_chave' => 'required_if:tipo,pix|nullable|in:cpf,cnpj,email,telefone,aleatoria',
        'pix_chave' => 'required_if:tipo,pix|nullable|string|max:77',
    ];

    public function __construct(CobrancaRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    public function store(Request $request): JsonResponse
    {
        return parent::store($request);
    }

    public function show(string $id): JsonResponse
    {
        return parent::show($id);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return parent::update($request, $id);
    }

    public function destroy(Request $request): JsonResponse
    {
        return parent::destroy($request);
    }

    public function pagar(string $id): JsonResponse
    {
        try {
            return response()->json($this->repository->pagar($id))
                ->setStatusCode(Response::HTTP_OK);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }
}
