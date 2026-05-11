<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NewsPreviewResource;
use App\Models\News;
use App\Services\NewsPreviewTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreviewNewsController extends Controller
{
    public function __construct(private readonly NewsPreviewTokenService $tokenService)
    {
    }

    public function show(Request $request, int $id): NewsPreviewResource|JsonResponse
    {
        $news = News::query()->findOrFail($id);

        if ($request->user('sanctum')?->can('news.preview') || $this->tokenService->isValid($news, $request->query('token'))) {
            return new NewsPreviewResource($news);
        }

        abort(403, 'Invalid preview token.');
    }

    public function generate(Request $request, int $id): JsonResponse
    {
        $news = News::query()->findOrFail($id);
        $this->authorize('preview', $news);

        $token = $this->tokenService->generate($news, $request->user(), (int) $request->integer('minutes', 60));

        return response()->json([
            'token' => $token,
            'expires_in_minutes' => (int) $request->integer('minutes', 60),
            'frontend_preview_url' => '/news-preview/'.$news->id.'?token='.$token,
        ]);
    }
}
