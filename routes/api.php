<?php

use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\PreviewNewsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/actuality', [NewsController::class, 'actuality']);
    Route::get('/news/{slug}', [NewsController::class, 'show']);

    Route::get('/preview/news/{id}', [PreviewNewsController::class, 'show']);
    Route::middleware('auth:sanctum')->post('/preview/news/{id}/token', [PreviewNewsController::class, 'generate']);
});
