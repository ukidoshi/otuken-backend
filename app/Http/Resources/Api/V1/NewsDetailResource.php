<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\NewsApiLocale;
use App\Services\Media\EditorJsMediaMetadata;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = NewsApiLocale::fromRequest($request);
        $cover = $this->getFirstMedia('cover_image');
        $seo = $this->getFirstMedia('seo_image');

        $blocks = $this->getTranslation('content_blocks', $locale, true);
        if (is_array($blocks)) {
            $blocks = app(EditorJsMediaMetadata::class)->enrichContent($blocks);
        }

        return [
            'title' => $this->getTranslation('title', $locale, true),
            'slug' => $this->slug,
            'excerpt' => $this->getTranslation('excerpt', $locale, true),
            'content_blocks' => $blocks,
            'actuality_highlight' => (bool) $this->is_actuality_highlight,
            'cover_url' => $cover?->getUrl('webp-1400') ?? $cover?->getUrl(),
            'cover_alt' => $cover?->getCustomProperty('alt'),
            'published_at' => optional($this->publish_at)->toIso8601String(),
            'date_text' => optional($this->publish_at)->translatedFormat('d.m.Y H:i'),
            'seo_title' => $this->getTranslation('seo_title', $locale, true),
            'seo_description' => $this->getTranslation('seo_description', $locale, true),
            'seo_image_url' => $seo?->getUrl('webp-800') ?? $seo?->getUrl(),
            'seo_image_alt' => $this->seo_image_alt,
            'canonical' => $this->canonical,
            'locale' => $this->locale,
        ];
    }
}
