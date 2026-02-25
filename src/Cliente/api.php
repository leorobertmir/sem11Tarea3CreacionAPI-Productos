<?php

use Illuminate\Support\Facades\Route;
use Src\Cliente\Application\Controllers\ClienteController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('clientes', ClienteController::class)->names([
        'index' => 'clientes.index',
        'store' => 'clientes.store',
        'show' => 'clientes.show',
        'update' => 'clientes.update',
        'destroy' => 'clientes.destroy',
    ]);
});