<?php

declare(strict_types=1);

namespace App\Domain\Auth\Controllers;

use App\Domain\Auth\Requests\LoginRequest;
use App\Domain\Auth\Resources\UserResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->autenticar();

        $token = $user->createToken(
            name: substr((string) $request->userAgent(), 0, 100) ?: 'api',
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'expira_em' => $this->expiracaoDoToken(),
            'usuario' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(
            ['mensagem' => 'Sessão encerrada.'],
            Response::HTTP_OK
        );
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(
            new UserResource($request->user())
        );
    }

    private function expiracaoDoToken(): ?string
    {
        $minutos = config('sanctum.expiration');

        return $minutos ? now()->addMinutes((int) $minutos)->toIso8601String() : null;
    }
}
