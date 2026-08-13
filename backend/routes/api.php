<?php

declare(strict_types=1);

use App\Domain\Auth\Controllers\AuthController;
use App\Domain\Cliente\Controllers\ClienteController;
use App\Domain\Cobranca\Controllers\CobrancaController;
use App\Domain\Contrato\Controllers\ContratoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'aplicacao' => config('app.name'),
    'status' => 'ok',
]));

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::post('/clientes', [ClienteController::class, 'store']);
    Route::get('/clientes/{id}', [ClienteController::class, 'show']);
    Route::put('/clientes/{id}', [ClienteController::class, 'update']);
    Route::delete('/clientes', [ClienteController::class, 'destroy']);
    Route::get('/clientes/{id}/check-delete', [ClienteController::class, 'checkDelete']);
    Route::patch('/clientes/{id}/inactive', [ClienteController::class, 'inactive']);
    Route::patch('/clientes/{id}/active', [ClienteController::class, 'active']);

    Route::get('/contratos', [ContratoController::class, 'index']);
    Route::post('/contratos', [ContratoController::class, 'store']);
    Route::get('/contratos/{id}', [ContratoController::class, 'show']);
    Route::put('/contratos/{id}', [ContratoController::class, 'update']);
    Route::delete('/contratos', [ContratoController::class, 'destroy']);
    Route::patch('/contratos/{id}/suspender', [ContratoController::class, 'suspender']);
    Route::patch('/contratos/{id}/reativar', [ContratoController::class, 'reativar']);
    Route::patch('/contratos/{id}/encerrar', [ContratoController::class, 'encerrar']);

    Route::get('/cobrancas', [CobrancaController::class, 'index']);
    Route::post('/cobrancas', [CobrancaController::class, 'store']);
    Route::get('/cobrancas/{id}', [CobrancaController::class, 'show']);
    Route::put('/cobrancas/{id}', [CobrancaController::class, 'update']);
    Route::delete('/cobrancas', [CobrancaController::class, 'destroy']);
    Route::patch('/cobrancas/{id}/pagar', [CobrancaController::class, 'pagar']);
});
