<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\NewsDetailResource;
use App\Models\News;
use App\Services\NewsPreviewTokenService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NewsPreviewPageController extends Controller
{
    public function __construct(private readonly NewsPreviewTokenService $tokenService)
    {
    }

    public function show(Request $request, int $id): View
    {
        $news = News::query()->findOrFail($id);

        $hasPermission = (bool) $request->user()?->can('news.preview');
        $hasValidToken = $this->tokenService->isValid($news, $request->query('token'));

        if (! $hasPermission && ! $hasValidToken) {
            return response()
                ->view('news.preview', [
                    'newsData' => null,
                    'isInvalidToken' => true,
                ], 403);
        }

        $newsData = (new NewsDetailResource($news))->toArray($request);
        $newsData['status'] = $news->status->value;

        return view('news.preview', [
            'newsData' => $newsData,
            'isInvalidToken' => false,
        ]);
    }
}
