<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return (new NewsDetailResource($this->resource))->toArray($request) + [
            'status' => $this->status->value,
            'preview_url' => '/news-preview/'.$this->id.'?token='.$request->query('token'),
        ];
    }
}
