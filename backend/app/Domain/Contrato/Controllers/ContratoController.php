<?php

declare(strict_types=1);

namespace App\Domain\Contrato\Controllers;

use App\Domain\Contrato\Repositories\ContratoRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContratoController extends Controller
{
    protected ContratoRepository $repository;

    protected array $validators = [
        'cliente_id' => 'required|integer|exists:clientes,id',
        'numero' => 'required|string|max:30|unique:contratos,numero',
        'ciclo' => 'required|integer|min:1|max:31',
        'valor_mensal' => 'required|numeric|min:0.01',
        'data_inicio' => 'required|date',
        'data_fim' => 'nullable|date|after:data_inicio',
    ];

    public function __construct(ContratoRepository $repository)
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

    public function suspender(string $id): JsonResponse
    {
        try {
            return response()->json($this->repository->suspender($id))
                ->setStatusCode(Response::HTTP_OK);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }

    public function reativar(string $id): JsonResponse
    {
        try {
            return response()->json($this->repository->reativar($id))
                ->setStatusCode(Response::HTTP_OK);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }

    public function encerrar(string $id): JsonResponse
    {
        try {
            return response()->json($this->repository->encerrar($id))
                ->setStatusCode(Response::HTTP_OK);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }
}
