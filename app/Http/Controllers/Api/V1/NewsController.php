<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NewsDetailResource;
use App\Http\Resources\Api\V1\NewsListResource;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NewsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return NewsListResource::collection(
            News::query()
                ->publiclyVisible()
                ->latest('publish_at')
                ->paginate(10)
        );
    }

    public function show(Request $request, string $slug): NewsDetailResource
    {
        $news = News::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        return new NewsDetailResource($news);
    }

    /**
     * Одна закрепленная новость для главной страницы (или null, если закрепления нет).
     */
    public function actuality(Request $request): JsonResponse
    {
        $news = News::query()
            ->publiclyVisible()
            ->where('is_actuality_highlight', true)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'data' => $news ? (new NewsDetailResource($news))->resolve() : null,
        ]);
    }
}
