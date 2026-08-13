<?php

declare(strict_types=1);

use App\Domain\Auth\Controllers\AuthController;
use App\Domain\Cliente\Controllers\ClienteController;
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
});
