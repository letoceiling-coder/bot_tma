<?php

use App\Http\Controllers\DeployController;
use App\Http\Controllers\LogsController;
use Illuminate\Support\Facades\Route;

// Роут для развертывания (без CSRF, защита через DEPLOY_SECRET)
// Исключен из CSRF в app/Http/Middleware/VerifyCsrfToken.php
Route::post('/deploy', [DeployController::class, 'deploy']);

// Роуты для просмотра логов (без CSRF, защита через DEPLOY_TOKEN)
Route::get('/logs', [LogsController::class, 'index']);
Route::get('/logs/list', [LogsController::class, 'list']);
Route::post('/logs/clear', [LogsController::class, 'clear']);

Route::group([ 'middleware' => ['locale.set']], function () {


    Route::get('{any?}', function () {

        return view('layouts.app');
    })->where('any', '.*');
});
