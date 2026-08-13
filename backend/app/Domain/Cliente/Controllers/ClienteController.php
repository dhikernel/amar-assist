<?php

declare(strict_types=1);

namespace App\Domain\Cliente\Controllers;

use App\Domain\Cliente\Repositories\ClienteRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClienteController extends Controller
{
    protected ClienteRepository $repository;

    protected array $validators = [
        'nome' => 'required|string|min:3|max:150',
        'cpf_cnpj' => 'required|string|cpf_cnpj|cpf_cnpj_unico:clientes,cpf_cnpj',
        'cep' => 'required|string|regex:/^\d{5}-?\d{3}$/',
        'logradouro' => 'required|string|max:150',
        'numero' => 'required|string|max:20',
        'complemento' => 'nullable|string|max:100',
        'bairro' => 'required|string|max:100',
        'cidade' => 'required|string|max:100',
        'uf' => 'required|string|uf',
        'email' => 'nullable|string|email:rfc,strict|max:150',
        'telefone' => 'required|string|regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/',
    ];

    public function __construct(ClienteRepository $repository)
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

    public function checkDelete(string $id): JsonResponse
    {
        return parent::checkDelete($id);
    }

    public function inactive(string $id): JsonResponse
    {
        return parent::inactive($id);
    }

    public function active(string $id): JsonResponse
    {
        try {
            return response()->json($this->repository->active($id))
                ->setStatusCode(Response::HTTP_OK);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }
}
