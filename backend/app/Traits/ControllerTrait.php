<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

trait ControllerTrait
{
    public function index(Request $request): JsonResponse
    {
        try {
            if (! empty($this->repository)) {
                return response()->json($this->repository->index($request->all()))
                    ->setStatusCode(Response::HTTP_OK);
            }

            return response()->json(null, Response::HTTP_NOT_FOUND);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->validators ?? []);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()])
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            if (! empty($this->repository)) {
                $result = $this->repository->store($validator->validated());

                return response()->json($result)
                    ->setStatusCode(Response::HTTP_CREATED);
            }

            return response()->json(null, Response::HTTP_NOT_FOUND);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            if (! empty($this->repository)) {
                $result = $this->repository->getById($id);

                if ($result === null) {
                    return response()->json(null, Response::HTTP_NOT_FOUND);
                }

                return response()->json($result)
                    ->setStatusCode(Response::HTTP_OK);
            }

            return response()->json(null, Response::HTTP_NOT_FOUND);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->validatorsUpdate($id));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()])
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            if (! empty($this->repository)) {
                $result = $this->repository->update($validator->validated(), $id);

                return response()->json($result)
                    ->setStatusCode(Response::HTTP_OK);
            }

            return response()->json(null, Response::HTTP_NOT_FOUND);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            if (! empty($this->repository)) {
                $this->repository->destroy($request->input('uuid'));

                return response()->json(null, Response::HTTP_NO_CONTENT);
            }

            return response()->json(null, Response::HTTP_NOT_FOUND);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }

    public function checkDelete(string $id): JsonResponse
    {
        $count = $this->repository->checkDelete($id);

        return response()->json([
            'count' => $count,
            'haveRelationship' => ! empty($count),
        ])->setStatusCode(Response::HTTP_OK);
    }

    public function inactive(string $id): JsonResponse
    {
        try {
            return response()->json($this->repository->inactive($id))
                ->setStatusCode(Response::HTTP_OK);
        } catch (\Throwable $exception) {
            return $this->respostaDeErro($exception);
        }
    }

    protected function validatorsUpdate(string $id): array
    {
        $regras = [];

        foreach ($this->validators ?? [] as $campo => $regra) {
            if (is_array($regra)) {
                array_unshift($regra, 'sometimes');

                $regras[$campo] = $regra;

                continue;
            }

            $regras[$campo] = 'sometimes|'.$this->ignorarProprioRegistroNoUnique((string) $regra, $id);
        }

        return $regras;
    }

    private function ignorarProprioRegistroNoUnique(string $regra, string $id): string
    {
        return preg_replace_callback('/(unique|cpf_cnpj_unico):([^|]+)/', function (array $encontrado) use ($id): string {
            $parametros = explode(',', $encontrado[2]);

            if (count($parametros) < 3) {
                $parametros[] = $id;
            }

            return $encontrado[1].':'.implode(',', $parametros);
        }, $regra);
    }

    protected function respostaDeErro(\Throwable $exception): JsonResponse
    {
        $jaTemStatusHttp = $exception instanceof HttpExceptionInterface
            || $exception instanceof ModelNotFoundException
            || $exception instanceof ValidationException
            || $exception instanceof AuthenticationException
            || $exception instanceof AuthorizationException;

        if ($jaTemStatusHttp) {
            throw $exception;
        }

        report($exception);

        return response()->json(['message' => 'Não foi possível concluir a operação.'])
            ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
