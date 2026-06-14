<?php

use App\Http\Controllers\Api\DistrictInterestController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\PreviewNewsController;
use App\Http\Controllers\Api\V1\SiteContentController;
use Illuminate\Support\Facades\Route;

Route::post('/leads/district-interest', [DistrictInterestController::class, 'store'])
    ->middleware('throttle:12,1');

Route::prefix('v1')->group(function (): void {
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/actuality', [NewsController::class, 'actuality']);
    Route::get('/news/{slug}', [NewsController::class, 'show']);

    Route::get('/site-content', [SiteContentController::class, 'show']);

    Route::get('/preview/news/{id}', [PreviewNewsController::class, 'show']);
    Route::middleware('auth:sanctum')->post('/preview/news/{id}/token', [PreviewNewsController::class, 'generate']);
});
