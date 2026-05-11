<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $title = trim((string) ($newsData['seo_title'] ?? '')) !== '' ? (string) $newsData['seo_title'] : (string) ($newsData['title'] ?? 'Preview');
        $description = trim((string) ($newsData['seo_description'] ?? '')) !== '' ? (string) $newsData['seo_description'] : (string) ($newsData['excerpt'] ?? '');
        $ogImage = trim((string) ($newsData['seo_image_url'] ?? '')) !== '' ? (string) $newsData['seo_image_url'] : (string) ($newsData['cover_url'] ?? '');
        $canonical = trim((string) ($newsData['canonical'] ?? ''));
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    @if($ogImage !== '')
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    @if($canonical !== '')
        <link rel="canonical" href="{{ $canonical }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/news-preview.css') }}">
</head>
<body>
<main class="preview-page">
    <div class="preview-container">
        @if($isInvalidToken)
            <section class="theme-card error-card" aria-live="polite">
                <span class="section-badge">
                    <span class="section-dot" aria-hidden="true"></span>
                    Предпросмотр недоступен
                </span>
                <h1 class="display-font section-title">Токен предпросмотра недействителен или истек</h1>
                <p class="lead">Сгенерируйте новый токен в админ-панели и откройте обновленную ссылку предпросмотра.</p>
            </section>
        @elseif(is_array($newsData))
            @php
                $status = (string) ($newsData['status'] ?? 'draft');
                $statusLabels = [
                    'draft' => 'draft',
                    'scheduled' => 'scheduled',
                    'published' => 'published',
                    'hidden' => 'hidden',
                    'archived' => 'archived',
                ];
                $statusLabel = $statusLabels[$status] ?? 'draft';

                $rawBlocks = $newsData['content_blocks'] ?? [];
                $blocks = [];
                if (is_array($rawBlocks) && array_key_exists('blocks', $rawBlocks) && is_array($rawBlocks['blocks'])) {
                    $blocks = $rawBlocks['blocks'];
                } elseif (is_array($rawBlocks)) {
                    $blocks = $rawBlocks;
                }

                $sanitizeText = static fn (mixed $value): string => trim(strip_tags((string) $value));
            @endphp

            <article class="theme-card hero-card">
                <header class="hero-header">
                    <span class="section-badge">
                        <span class="section-dot" aria-hidden="true"></span>
                        Предпросмотр новости
                    </span>
                    <span class="status-badge status-{{ $statusLabel }}">{{ $statusLabel }}</span>
                </header>

                <div class="hero-grid">
                    <section>
                        <h1 class="display-font section-title">{{ $sanitizeText($newsData['title'] ?? '') }}</h1>

                        @if(!empty($newsData['excerpt']))
                            <p class="lead">{{ $sanitizeText($newsData['excerpt']) }}</p>
                        @endif

                        <div class="meta-row">
                            @if(!empty($newsData['locale']))
                                <span class="meta-chip">{{ strtoupper($sanitizeText($newsData['locale'])) }}</span>
                            @endif
                            @if(!empty($newsData['date_text']))
                                <time datetime="{{ $sanitizeText($newsData['published_at'] ?? '') }}" class="meta-chip">
                                    {{ $sanitizeText($newsData['date_text']) }}
                                </time>
                            @endif
                            <span class="meta-chip">status: {{ $statusLabel }}</span>
                        </div>
                    </section>

                    @if(!empty($newsData['cover_url']))
                        <figure class="cover-wrap">
                            <img
                                src="{{ $newsData['cover_url'] }}"
                                alt="{{ $sanitizeText($newsData['cover_alt'] ?? $newsData['title'] ?? 'News cover') }}"
                                loading="lazy"
                                decoding="async"
                                class="cover-image"
                            >
                        </figure>
                    @endif
                </div>
            </article>

            <section class="theme-card content-card" aria-label="Контент новости">
                @forelse($blocks as $block)
                    @php
                        $type = (string) ($block['type'] ?? '');
                        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
                    @endphp

                    @if(in_array($type, ['heading', 'header'], true))
                        @php $level = (int) ($data['level'] ?? 2); @endphp
                        @if($level === 3)
                            <h3 class="display-font content-heading h3">{{ $sanitizeText($data['text'] ?? '') }}</h3>
                        @else
                            <h2 class="display-font content-heading">{{ $sanitizeText($data['text'] ?? '') }}</h2>
                        @endif
                    @elseif($type === 'paragraph')
                        <p class="content-paragraph">{{ $sanitizeText($data['text'] ?? '') }}</p>
                    @elseif($type === 'quote')
                        <blockquote class="content-quote">
                            <p>{{ $sanitizeText($data['text'] ?? '') }}</p>
                            @if(!empty($data['caption']))
                                <cite>{{ $sanitizeText($data['caption']) }}</cite>
                            @endif
                        </blockquote>
                    @elseif($type === 'list')
                        @php
                            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
                            $listStyle = strtolower((string) ($data['style'] ?? 'unordered'));
                        @endphp
                        @if(!empty($items))
                            @if($listStyle === 'checklist')
                                <ul class="content-list content-checklist">
                                    @foreach($items as $item)
                                        @php
                                            $line = '';
                                            $checked = false;
                                            if (is_array($item)) {
                                                $line = (string) ($item['content'] ?? $item['text'] ?? '');
                                                $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];
                                                $checked = (bool) ($meta['checked'] ?? ($item['checked'] ?? false));
                                            } else {
                                                $line = (string) $item;
                                            }
                                        @endphp
                                        @if($line !== '')
                                            <li>
                                                <input type="checkbox" disabled @checked($checked) aria-hidden="true">
                                                <span>{{ $sanitizeText($line) }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @else
                                <ul class="content-list">
                                    @foreach($items as $item)
                                        @php
                                            $line = is_array($item)
                                                ? (string) ($item['content'] ?? $item['text'] ?? '')
                                                : (string) $item;
                                        @endphp
                                        @if($line !== '')
                                            <li><span class="section-dot" aria-hidden="true"></span><span>{{ $sanitizeText($line) }}</span></li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        @endif
                    @elseif($type === 'checklist')
                        @php $clItems = is_array($data['items'] ?? null) ? $data['items'] : []; @endphp
                        @if(!empty($clItems))
                            <ul class="content-list content-checklist">
                                @foreach($clItems as $clItem)
                                    @if(is_array($clItem))
                                        @php
                                            $clLine = (string) ($clItem['text'] ?? '');
                                            $clChecked = (bool) ($clItem['checked'] ?? false);
                                        @endphp
                                        @if($clLine !== '')
                                            <li>
                                                <input type="checkbox" disabled @checked($clChecked) aria-hidden="true">
                                                <span>{{ $sanitizeText($clLine) }}</span>
                                            </li>
                                        @endif
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    @elseif($type === 'image')
                        @php
                            $url = $data['file']['url'] ?? $data['url'] ?? null;
                            $caption = $sanitizeText($data['caption'] ?? '');
                        @endphp
                        @if(is_string($url) && $url !== '')
                            <figure class="content-figure">
                                <img src="{{ $url }}" alt="{{ $caption !== '' ? $caption : $sanitizeText($newsData['title'] ?? 'News image') }}" loading="lazy" decoding="async">
                                @if($caption !== '')
                                    <figcaption>{{ $caption }}</figcaption>
                                @endif
                            </figure>
                        @endif
                    @elseif($type === 'video')
                        @php
                            $videoUrl = $data['file']['url'] ?? $data['url'] ?? null;
                            $captionVideo = $sanitizeText($data['caption'] ?? '');
                        @endphp
                        @if(is_string($videoUrl) && $videoUrl !== '')
                            <figure class="content-video">
                                <video
                                    src="{{ $videoUrl }}"
                                    controls
                                    playsinline
                                    preload="metadata"
                                    @if($captionVideo !== '') aria-label="{{ $captionVideo }}" @endif
                                ></video>
                                @if($captionVideo !== '')
                                    <figcaption>{{ $captionVideo }}</figcaption>
                                @endif
                            </figure>
                        @endif
                    @elseif($type === 'embed')
                        @php
                            $embedUrl = isset($data['embed']) && is_string($data['embed']) ? trim($data['embed']) : '';
                            $isAllowedEmbed = $embedUrl !== ''
                                && (bool) preg_match('#^https://(www\.youtube\.com/embed/|www\.youtube-nocookie\.com/embed/|player\.vimeo\.com/video/)#i', $embedUrl);
                            $captionEmbed = isset($data['caption']) && is_string($data['caption']) ? $sanitizeText(strip_tags($data['caption'])) : '';
                            $sourceUrl = isset($data['source']) && is_string($data['source']) ? trim($data['source']) : '';
                            $sourceSafe = $sourceUrl !== '' && (bool) preg_match('#^https?://#i', $sourceUrl);
                        @endphp
                        @if($isAllowedEmbed)
                            <figure class="content-embed">
                                <div class="content-embed-frame">
                                    <iframe
                                        src="{{ $embedUrl }}"
                                        title="{{ $captionEmbed !== '' ? $captionEmbed : 'Видео' }}"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                        loading="lazy"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                    ></iframe>
                                </div>
                                @if($captionEmbed !== '')
                                    <figcaption class="content-embed-caption">{{ $captionEmbed }}</figcaption>
                                @endif
                            </figure>
                        @elseif($sourceSafe)
                            <p class="content-paragraph">
                                <a href="{{ $sourceUrl }}" rel="noopener noreferrer" target="_blank">{{ $sanitizeText($sourceUrl) }}</a>
                            </p>
                        @endif
                    @endif
                @empty
                    <p class="content-empty">Контент еще не добавлен.</p>
                @endforelse
            </section>
        @endif
    </div>
</main>
</body>
</html>
