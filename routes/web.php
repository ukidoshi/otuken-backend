<?php

use App\Http\Controllers\Admin\NewsStudioController;
use App\Http\Controllers\NewsPreviewPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('admin/studio/news')->group(function (): void {
    Route::get('/{news}', [NewsStudioController::class, 'edit'])->name('admin.news.studio.edit');
    Route::post('/{news}', [NewsStudioController::class, 'update'])->name('admin.news.studio.update');
    Route::post('/{news}/upload-image', [NewsStudioController::class, 'uploadImage'])->name('admin.news.studio.upload-image');
    Route::post('/{news}/upload-video', [NewsStudioController::class, 'uploadVideo'])->name('admin.news.studio.upload-video');
    Route::post('/{news}/translate-en', [NewsStudioController::class, 'translateToEn'])->name('admin.news.studio.translate-en');
    Route::post('/{news}/translate-tuv', [NewsStudioController::class, 'translateToTuv'])->name('admin.news.studio.translate-tuv');
});

Route::get('/news-preview/{id}', [NewsPreviewPageController::class, 'show'])->name('news.preview.page');
