<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\NewsApiLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = NewsApiLocale::fromRequest($request);
        $cover = $this->getFirstMedia('cover_image');

        return [
            'title' => $this->getTranslation('title', $locale, true),
            'slug' => $this->slug,
            'excerpt' => $this->getTranslation('excerpt', $locale, true),
            'actuality_highlight' => (bool) $this->is_actuality_highlight,
            'cover_url' => $cover?->getUrl('webp-800') ?? $cover?->getUrl(),
            'cover_alt' => $cover?->getCustomProperty('alt'),
            'published_at' => optional($this->publish_at)->toIso8601String(),
            'date_text' => optional($this->publish_at)->translatedFormat('d.m.Y H:i'),
            'seo_title' => $this->getTranslation('seo_title', $locale, true),
            'seo_description' => $this->getTranslation('seo_description', $locale, true),
            'seo_image_alt' => $this->seo_image_alt,
            'canonical' => $this->canonical,
            'locale' => $this->locale,
        ];
    }
}
