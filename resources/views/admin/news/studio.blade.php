<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Контент-студия — {{ $news->slug }}</title>
    <style>
        html { height: 100%; }
        body { margin: 0; min-height: 100%; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #0b0d12; color: #e5e7eb; }
        .bar { display:flex; justify-content:space-between; align-items:center; gap: 10px; padding:12px 16px; border-bottom:1px solid #222834; position: sticky; top: 0; background:#0b0d12; z-index: 10; }
        /* Две колонки одинаковой высоты: flex + stretch надёжнее grid при вложенном скролле */
        .wrap {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            gap: 12px;
            padding: 12px;
            min-height: calc(100vh - 62px);
            box-sizing: border-box;
        }
        .card {
            flex: 1 1 0;
            min-width: 0;
            border: 1px solid #222834;
            border-radius: 12px;
            background: #11151d;
            padding: 12px;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }
        /* Плавающие тулбары Editor.js не обрезаем */
        .card.editor-card { overflow: visible; }
        .card.preview-card { overflow: visible; }
        /* Не делаем этот контейнер flex-колонкой: у Editor.js ломается вёрстка блоков */
        .editor-scroll {
            flex: 1 1 0;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 10px 8px 8px 0;
            -webkit-overflow-scrolling: touch;
        }
        .muted { color:#9ca3af; font-size:12px; }
        .actions { display:flex; gap:8px; align-items:center; flex-wrap: wrap; justify-content: flex-end; }
        button, .btn { background:#f59e0b; color:#111827; border:none; border-radius:8px; padding:8px 12px; font-weight:600; cursor:pointer; text-decoration:none; }
        select { background:#0f172a; color:#e5e7eb; border:1px solid #374151; border-radius:8px; padding:8px; }
        #status { font-size:13px; color:#9ca3af; }
        #preview {
            flex: 1 1 auto;
            width: 100%;
            min-height: 280px;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px 14px 16px 56px;
            box-sizing: border-box;
        }
        #preview .preview-inner {
            max-width: 760px;
        }
        #preview .preview-block { margin: 0 0 0.85em; line-height: 1.65; }
        #preview .preview-block:last-child { margin-bottom: 0; }
        #preview h2.preview-h, #preview h3.preview-h, #preview h4.preview-h {
            margin: 1.1em 0 0.5em;
            font-weight: 700;
            line-height: 1.25;
            color: #0f172a;
        }
        #preview h2.preview-h { font-size: 1.65rem; }
        #preview h3.preview-h { font-size: 1.35rem; }
        #preview h4.preview-h { font-size: 1.15rem; }
        #preview .preview-p { font-size: 1rem; }
        #preview .preview-p a { color: #2563eb; text-decoration: underline; }
        #preview .preview-list { margin: 0.5em 0 1em 1.25em; padding: 0; }
        #preview .preview-list li { margin: 0.35em 0; }
        #preview .preview-quote {
            margin: 1em 0;
            padding: 0.75em 1em;
            border-left: 4px solid #e5e7eb;
            background: #f9fafb;
            color: #111827;
        }
        #preview .preview-quote cite {
            display: block;
            margin-top: 0.5em;
            font-size: 0.875rem;
            color: #6b7280;
            font-style: normal;
        }
        #preview .preview-checklist { list-style: none; margin: 0.5em 0 1em; padding: 0; }
        #preview .preview-checklist li { margin: 0.4em 0; display: flex; align-items: flex-start; gap: 0.5em; }
        #preview .preview-checklist input { margin-top: 0.2em; flex-shrink: 0; }
        #preview .preview-delimiter {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 1.25em 0;
        }
        #preview .preview-embed {
            margin: 1em 0;
            padding: 12px;
            background: #f3f4f6;
            border-radius: 8px;
            overflow: auto;
        }
        #preview .preview-embed iframe { max-width: 100%; border: 0; }
        #preview .preview-image { margin: 1em 0; text-align: center; }
        #preview .preview-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            display: inline-block;
        }
        #preview .preview-video { margin: 1em 0; text-align: center; }
        #preview .preview-video video {
            max-width: 100%;
            max-height: 420px;
            border-radius: 8px;
            background: #111827;
        }
        #preview .preview-caption {
            margin-top: 0.5em;
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* Overlay для удаления блока видео: появляется при hover/focus */
        #editorjs .video-tool {
            position: relative;
        }
        #editorjs .studio-block-delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(220, 38, 38, 0.92);
            color: #fff;
            border-radius: 50%;
            border: 0;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            opacity: 0;
            transition: opacity 120ms ease;
            z-index: 5;
            padding: 0;
        }
        #editorjs .video-tool:hover .studio-block-delete-btn,
        #editorjs .video-tool:focus-within .studio-block-delete-btn,
        #editorjs .studio-block-delete-btn:focus {
            opacity: 1;
        }
        #editorjs .studio-block-delete-btn:hover {
            background: #b91c1c;
        }

        /* Editor.js native-like theme */
        #editorjs {
            position: relative;
            width: 100%;
            /* Не ниже 360px и не ниже высоты колонки — визуально совпадает с превью */
            min-height: max(360px, 100%);
            height: auto;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px 14px 16px 56px;
            box-sizing: border-box;
        }
        #editorjs .ce-block__content {
            max-width: 760px;
        }
        #editorjs .ce-toolbar__content {
            max-width: 760px;
            overflow: visible;
        }
        #editorjs .ce-toolbar,
        #editorjs .ce-toolbar__actions {
            overflow: visible;
        }
        #editorjs .ce-paragraph,
        #editorjs .cdx-block,
        #editorjs .ce-header {
            color: #0f172a;
        }
        #editorjs .ce-toolbar__plus,
        #editorjs .ce-toolbar__settings-btn {
            color: #111827;
        }
        #editorjs .ce-inline-toolbar,
        #editorjs .ce-conversion-toolbar,
        #editorjs .ce-settings {
            background: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 10px 25px rgba(2, 6, 23, 0.18);
        }
        #editorjs .ce-popover,
        #editorjs .ce-popover-item,
        #editorjs .ce-conversion-tool,
        #editorjs .ce-settings__button,
        #editorjs .ce-inline-tool {
            color: #111827;
        }
        #editorjs .ce-popover-item:hover,
        #editorjs .ce-settings__button:hover,
        #editorjs .ce-inline-tool:hover,
        #editorjs .ce-conversion-tool:hover {
            background: #f3f4f6;
        }
        #editorjs .cdx-input {
            color: #111827;
            background: #ffffff;
        }
        #editorjs .cdx-quote__caption {
            color: #4b5563;
        }
        @media (max-width: 1024px) {
            .bar {
                align-items: flex-start;
                flex-direction: column;
            }
            .actions {
                width: 100%;
                justify-content: flex-start;
            }
            .wrap {
                flex-direction: column;
                min-height: calc(100vh - 62px);
            }
            .card {
                flex: 0 1 auto;
                min-height: 0;
                width: 100%;
            }
            .editor-scroll {
                flex: 0 1 auto;
                min-height: 320px;
                max-height: min(70vh, 640px);
                overflow-y: auto;
                padding-right: 0;
            }
        }

        @media (max-width: 640px) {
            body {
                font-size: 15px;
            }
            .bar {
                padding: 10px 12px;
            }
            .wrap {
                gap: 10px;
                padding: 10px;
            }
            .card {
                padding: 10px;
                border-radius: 10px;
            }
            button,
            .btn,
            select {
                width: 100%;
                min-height: 40px;
            }
            .actions label {
                width: 100%;
            }
            #editorjs,
            #preview {
                min-height: 360px;
                padding: 12px 10px 12px 38px;
            }
            #editorjs .ce-block__content,
            #editorjs .ce-toolbar__content,
            #preview .preview-inner {
                max-width: 100%;
            }
            #preview h2.preview-h { font-size: 1.4rem; }
            #preview h3.preview-h { font-size: 1.2rem; }
            #preview h4.preview-h { font-size: 1.05rem; }
        }
    </style>
</head>
<body>
<div class="bar">
    <div>
        <div><strong>Контент-студия</strong> · Новость #{{ $news->id }}</div>
        <div class="muted">Отдельный редактор с live preview</div>
    </div>
    <div class="actions">
        <label class="muted" for="locale">Язык</label>
        <select id="locale" data-studio-base="{{ route('admin.news.studio.edit', $news) }}">
            <option value="ru" @selected($locale === 'ru')>RU</option>
            <option value="tuv" @selected($locale === 'tuv')>TUV</option>
            <option value="en" @selected($locale === 'en')>EN</option>
        </select>
        @if($locale === 'en')
            <button id="translate-en-btn" type="button" title="Берёт русский контент из RU и записывает перевод в EN">Перевести RU → EN</button>
        @endif
        @if($locale === 'tuv' && ! empty($enableTuvStudioTranslation))
            <button id="translate-tuv-btn" type="button" title="Берёт русский контент из RU и записывает перевод в TUV (качество зависит от модели)">Перевести RU → TUV</button>
        @endif
        <button id="save-btn" type="button">Сохранить</button>
        <a class="btn" href="{{ route('filament.admin.resources.news.edit', $news) }}">Назад в карточку</a>
    </div>
</div>

<div class="wrap">
    <div class="card editor-card">
        <div class="editor-scroll">
            <div id="editorjs"></div>
        </div>
    </div>
    <div class="card preview-card">
        <div class="muted" style="margin-bottom:8px;">Превью</div>
        <div id="preview"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/delimiter@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/embed@2.7.6/dist/embed.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@weekwood/editorjs-video@1.0.2/dist/bundle.js"></script>
<script src="{{ asset('js/editorjs-i18n-ru.js') }}"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    /** Один раз читаем тело ответа (иначе после .json() повторный .text() падает). */
    async function readUploadError(response, labelRu) {
        const text = await response.text();
        let msg = `${labelRu} (HTTP ${response.status})`;
        if (!text) {
            return msg;
        }
        try {
            const data = JSON.parse(text);
            return data.message || data.error || `${msg}: ${text.slice(0, 400)}`;
        } catch {
            return `${msg}: ${text.slice(0, 400)}`;
        }
    }

    const initialData = @json($initialContent);
    const listTool = window.List || window.EditorjsList;
    const delimiterTool = window.Delimiter || window.EditorjsDelimiter;
    const embedTool = window.Embed ?? window.EditorjsEmbed ?? null;
    /** Иконка для меню «+»: у Embed нет static toolbox в пакете */
    const embedToolboxIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path fill="currentColor" d="M8 5v14l11-7L8 5z"/></svg>';
    const videoToolboxIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path fill="currentColor" d="M4 6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2.5l4 2.5V6l-4 2.5V6a2 2 0 0 0-2-2H4zm0 2h12v8H4V8zm14 1.2v5.6L21 14V10l-3 1.2z"/></svg>';
    const imageTool = window.ImageTool || window.EditorjsImage;
    const rawVideoTool = window.VideoTool;

    /**
     * У плагина @weekwood/editorjs-video нет своей кнопки удаления блока.
     * Родная «корзина» в Block Tunes (≡ слева) перекрывается контролами <video>,
     * поэтому добавляем явную кнопку «Удалить» в попап настроек видео-блока.
     */
    const videoTool = (() => {
        if (!rawVideoTool) return null;

        return class VideoToolWithDelete extends rawVideoTool {
            constructor(opts) {
                super(opts);
                if (!this.api && opts && opts.api) {
                    this.api = opts.api;
                }
            }

            renderSettings() {
                const wrapper = super.renderSettings();
                if (!(wrapper instanceof HTMLElement)) return wrapper;

                const btn = document.createElement('div');
                btn.classList.add('cdx-settings-button');
                btn.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:6px;cursor:pointer;color:#dc2626;background:rgba(220,38,38,0.08);margin-top:4px;';
                btn.title = 'Удалить блок видео';
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>';
                btn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (!window.confirm('Удалить блок видео?')) return;
                    try {
                        this.api.blocks.delete();
                    } catch (e) {
                        console.warn('blocks.delete() failed, fallback', e);
                        const idx = this.api && this.api.blocks && this.api.blocks.getCurrentBlockIndex
                            ? this.api.blocks.getCurrentBlockIndex()
                            : -1;
                        if (idx >= 0) this.api.blocks.delete(idx);
                    }
                });

                wrapper.appendChild(btn);

                return wrapper;
            }
        };
    })();
    const preview = document.getElementById('preview');
    const statusNode = document.createElement('div');
    statusNode.id = 'status';
    document.querySelector('.bar').appendChild(statusNode);

    const unsavedHint = document.createElement('span');
    unsavedHint.id = 'unsaved-hint';
    unsavedHint.className = 'muted';
    unsavedHint.style.cssText = 'margin-left:10px;color:#fbbf24;font-weight:600;display:none;';
    unsavedHint.textContent = 'Есть несохранённые изменения';
    document.querySelector('.bar').appendChild(unsavedHint);

    const UNSAVED_LEAVE_MSG = 'У вас есть несохранённые изменения. Если вы уйдёте из контент-студии, они будут потеряны. Продолжить?';
    const TRANSLATING_LEAVE_MSG = 'Сейчас выполняется автоматический перевод с русского. Если вы уйдёте, запрос может прерваться и перевод останется неполным. Продолжить?';

    let savedSnapshot = '';
    let baselineReady = false;
    let isDirty = false;
    let isTranslating = false;

    function confirmLeaveNavigation() {
        if (isTranslating) {
            return window.confirm(TRANSLATING_LEAVE_MSG);
        }
        if (isDirty) {
            return window.confirm(UNSAVED_LEAVE_MSG);
        }

        return true;
    }

    function setDirty(dirty) {
        isDirty = dirty;
        unsavedHint.style.display = dirty ? 'inline' : 'none';
    }

    function snapshotEditorData(data) {
        return JSON.stringify(data ?? { blocks: [] });
    }

    const ALLOWED_INLINE_TAGS = new Set(['b', 'strong', 'i', 'em', 'u', 'a', 'code', 'mark', 'br', 'span']);

    function sanitizeInlineHtml(html) {
        if (html == null) {
            return '';
        }
        if (typeof html !== 'string') {
            return '';
        }
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;

        const walk = (node) => {
            if (node.nodeType === Node.TEXT_NODE) {
                return;
            }
            if (node.nodeType !== Node.ELEMENT_NODE) {
                node.remove();
                return;
            }
            const tag = node.tagName.toLowerCase();
            if (!ALLOWED_INLINE_TAGS.has(tag)) {
                const parent = node.parentNode;
                if (!parent) {
                    return;
                }
                while (node.firstChild) {
                    parent.insertBefore(node.firstChild, node);
                }
                parent.removeChild(node);
                return;
            }
            if (tag === 'a') {
                const href = node.getAttribute('href');
                if (!href || !/^https?:\/\//i.test(href)) {
                    node.removeAttribute('href');
                }
                node.setAttribute('rel', 'noopener noreferrer');
            }
            Array.from(node.childNodes).forEach(walk);
        };

        Array.from(wrapper.childNodes).forEach(walk);
        return wrapper.innerHTML;
    }

    function listItemText(item) {
        if (item == null) {
            return '';
        }
        if (typeof item === 'string') {
            return item;
        }
        if (typeof item === 'object') {
            if (typeof item.content === 'string') {
                return item.content;
            }
            if (typeof item.text === 'string') {
                return item.text;
            }
            if (Array.isArray(item.content)) {
                return item.content
                    .map((part) => (typeof part === 'string' ? part : (part && part.text) ? part.text : ''))
                    .join('');
            }
        }
        return '';
    }

    function renderPreview(data) {
        preview.innerHTML = '';
        const inner = document.createElement('div');
        inner.className = 'preview-inner';
        const blocks = (data && Array.isArray(data.blocks)) ? data.blocks : [];

        blocks.forEach((block) => {
            if (!block || !block.type) {
                return;
            }
            const d = block.data || {};
            let el;

            switch (block.type) {
                case 'header': {
                    const level = Math.min(Math.max(parseInt(d.level, 10) || 2, 2), 4);
                    el = document.createElement('h' + level);
                    el.className = 'preview-block preview-h';
                    el.innerHTML = sanitizeInlineHtml(d.text || '');
                    break;
                }
                case 'paragraph': {
                    el = document.createElement('p');
                    el.className = 'preview-block preview-p';
                    el.innerHTML = sanitizeInlineHtml(d.text || '');
                    break;
                }
                case 'list': {
                    const style = (d.style || 'unordered').toString().toLowerCase();
                    if (style === 'checklist') {
                        el = document.createElement('ul');
                        el.className = 'preview-block preview-checklist';
                        (d.items || []).forEach((item) => {
                            const li = document.createElement('li');
                            const input = document.createElement('input');
                            input.type = 'checkbox';
                            input.disabled = true;
                            input.checked = !!(item && ((item.meta && item.meta.checked) || item.checked));
                            const span = document.createElement('span');
                            span.innerHTML = sanitizeInlineHtml(listItemText(item));
                            li.appendChild(input);
                            li.appendChild(span);
                            el.appendChild(li);
                        });
                        break;
                    }
                    const tag = style === 'ordered' ? 'ol' : 'ul';
                    el = document.createElement(tag);
                    el.className = 'preview-block preview-list';
                    (d.items || []).forEach((item) => {
                        const li = document.createElement('li');
                        li.innerHTML = sanitizeInlineHtml(listItemText(item));
                        el.appendChild(li);
                    });
                    break;
                }
                case 'quote': {
                    el = document.createElement('blockquote');
                    el.className = 'preview-block preview-quote';
                    el.innerHTML = sanitizeInlineHtml(d.text || '');
                    if (d.caption) {
                        const cite = document.createElement('cite');
                        cite.textContent = d.caption;
                        el.appendChild(cite);
                    }
                    break;
                }
                case 'checklist': {
                    el = document.createElement('ul');
                    el.className = 'preview-block preview-checklist';
                    (d.items || []).forEach((item) => {
                        const li = document.createElement('li');
                        const input = document.createElement('input');
                        input.type = 'checkbox';
                        input.disabled = true;
                        input.checked = !!(item && item.checked);
                        const span = document.createElement('span');
                        span.innerHTML = sanitizeInlineHtml(listItemText(item));
                        li.appendChild(input);
                        li.appendChild(span);
                        el.appendChild(li);
                    });
                    break;
                }
                case 'delimiter': {
                    el = document.createElement('hr');
                    el.className = 'preview-block preview-delimiter';
                    break;
                }
                case 'embed': {
                    el = document.createElement('div');
                    el.className = 'preview-block preview-embed';
                    let appended = false;
                    if (typeof d.embed === 'string' && /<iframe/i.test(d.embed)) {
                        const tmp = document.createElement('div');
                        tmp.innerHTML = d.embed;
                        const iframe = tmp.querySelector('iframe');
                        if (iframe && iframe.getAttribute('src') && /^https?:\/\//i.test(iframe.getAttribute('src'))) {
                            const clone = iframe.cloneNode(true);
                            clone.removeAttribute('onload');
                            clone.removeAttribute('onerror');
                            el.appendChild(clone);
                            appended = true;
                        }
                    }
                    if (!appended && typeof d.embed === 'string' && /^https:\/\/(www\.youtube\.com\/embed\/|www\.youtube-nocookie\.com\/embed\/|player\.vimeo\.com\/video\/)/i.test(d.embed.trim())) {
                        const iframe = document.createElement('iframe');
                        iframe.src = d.embed.trim();
                        iframe.setAttribute('loading', 'lazy');
                        iframe.setAttribute('allowfullscreen', 'true');
                        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                        iframe.style.width = '100%';
                        iframe.style.aspectRatio = '16 / 9';
                        iframe.style.minHeight = '220px';
                        iframe.style.border = '0';
                        el.appendChild(iframe);
                        appended = true;
                    }
                    if (!appended && d.source) {
                        const a = document.createElement('a');
                        a.href = d.source;
                        a.rel = 'noopener noreferrer';
                        a.target = '_blank';
                        a.textContent = d.source;
                        el.appendChild(a);
                    }
                    break;
                }
                case 'image': {
                    const url = (d.file && d.file.url) ? d.file.url : (d.url || '');
                    if (!url) {
                        return;
                    }
                    el = document.createElement('figure');
                    el.className = 'preview-block preview-image';
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = d.caption || '';
                    img.loading = 'lazy';
                    el.appendChild(img);
                    if (d.caption) {
                        const cap = document.createElement('figcaption');
                        cap.className = 'preview-caption';
                        cap.textContent = d.caption;
                        el.appendChild(cap);
                    }
                    break;
                }
                case 'video': {
                    const vurl = (d.file && d.file.url) ? d.file.url : (d.url || '');
                    if (!vurl) {
                        return;
                    }
                    el = document.createElement('figure');
                    el.className = 'preview-block preview-video';
                    const vid = document.createElement('video');
                    vid.src = vurl;
                    vid.controls = true;
                    vid.setAttribute('playsinline', '');
                    vid.setAttribute('preload', 'metadata');
                    if (d.caption) {
                        vid.setAttribute('aria-label', d.caption);
                    }
                    el.appendChild(vid);
                    if (d.caption) {
                        const cap = document.createElement('figcaption');
                        cap.className = 'preview-caption';
                        cap.textContent = d.caption;
                        el.appendChild(cap);
                    }
                    break;
                }
                default:
                    return;
            }

            if (el) {
                inner.appendChild(el);
            }
        });

        preview.appendChild(inner);
    }

    const tools = {
        header: { class: Header, config: { levels: [2, 3, 4], defaultLevel: 2 } },
        list: { class: listTool, inlineToolbar: true },
        quote: { class: Quote, inlineToolbar: true },
    };

    if (delimiterTool) {
        tools.delimiter = { class: delimiterTool };
    }

    if (embedTool) {
        tools.embed = {
            class: embedTool,
            toolbox: {
                title: 'Видео',
                icon: embedToolboxIcon,
            },
            config: {
                services: {
                    youtube: true,
                    vimeo: true,
                },
            },
        };
    }

    if (imageTool) {
        tools.image = {
            class: imageTool,
            config: {
                uploader: {
                    async uploadByFile(file) {
                        const form = new FormData();
                        form.append('image', file);

                        const response = await fetch('{{ route('admin.news.studio.upload-image', $news) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: form,
                        });

                        if (!response.ok) {
                            const errorMessage = await readUploadError(response, 'Ошибка загрузки изображения');
                            statusNode.textContent = errorMessage;
                            throw new Error(errorMessage);
                        }

                        return await response.json();
                    },
                    async uploadByUrl(url) {
                        return {
                            success: 1,
                            file: { url },
                        };
                    },
                },
            },
        };
    }

    if (videoTool) {
        tools.video = {
            class: videoTool,
            toolbox: {
                title: 'Видео (файл)',
                icon: videoToolboxIcon,
            },
            config: {
                captionPlaceholder: 'Подпись к видео',
                /* @weekwood/editorjs-video ожидает config.player (react-player); иначе падает на o.player.pip */
                player: {
                    pip: false,
                    controls: true,
                    light: false,
                    playing: false,
                },
                uploader: {
                    async uploadByFile(file) {
                        const form = new FormData();
                        form.append('video', file);

                        const response = await fetch('{{ route('admin.news.studio.upload-video', $news) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: form,
                        });

                        if (!response.ok) {
                            const errorMessage = await readUploadError(response, 'Ошибка загрузки видео');
                            statusNode.textContent = errorMessage;
                            throw new Error(errorMessage);
                        }

                        return await response.json();
                    },
                    async uploadByUrl(url) {
                        return {
                            success: 1,
                            file: { url },
                        };
                    },
                },
            },
        };
    }

    let previewDebounce = null;

    window.addEventListener('beforeunload', (e) => {
        if (!isDirty && !isTranslating) {
            return;
        }
        e.preventDefault();
        e.returnValue = '';
    });

    document.addEventListener('click', (e) => {
        const a = e.target.closest('a');
        if (!a) {
            return;
        }
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }
        if (a.getAttribute('target') === '_blank') {
            return;
        }
        if (!isDirty && !isTranslating) {
            return;
        }
        if (!confirmLeaveNavigation()) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);

    const localeSelect = document.getElementById('locale');
    const studioBaseUrl = localeSelect.dataset.studioBase || '';
    localeSelect.addEventListener('change', () => {
        const next = studioBaseUrl + '?locale=' + encodeURIComponent(localeSelect.value);
        if (!confirmLeaveNavigation()) {
            localeSelect.value = '{{ $locale }}';

            return;
        }
        window.location.href = next;
    });

    const editorHolder = document.getElementById('editorjs');

    /**
     * Кладёт явную кнопку «удалить» в каждый блок видео.
     * Editor.js часто не реагирует на клик по нашему <video controls> и не показывает Block Tunes,
     * поэтому даём пользователю прямой контроль.
     */
    function attachVideoDeleteButtons() {
        if (!editorHolder) return;

        editorHolder.querySelectorAll('.video-tool').forEach((videoNode) => {
            if (videoNode.querySelector(':scope > .studio-block-delete-btn')) return;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'studio-block-delete-btn';
            btn.setAttribute('aria-label', 'Удалить блок видео');
            btn.title = 'Удалить блок видео';
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';

            btn.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (!window.confirm('Удалить блок видео?')) return;

                const ceBlock = videoNode.closest('.ce-block');
                if (!ceBlock) return;

                const redactor = editorHolder.querySelector('.codex-editor__redactor');
                if (!redactor) return;

                const all = Array.from(redactor.children).filter((n) => n.classList.contains('ce-block'));
                const idx = all.indexOf(ceBlock);

                if (idx < 0) return;

                try {
                    await editor.blocks.delete(idx);
                } catch (e) {
                    console.warn('blocks.delete() failed', e);
                }
            });

            videoNode.appendChild(btn);
        });
    }

    const editor = new EditorJS({
        holder: 'editorjs',
        data: initialData,
        minHeight: 320,
        tools,
        ...(window.editorJsI18nRu ? { i18n: window.editorJsI18nRu } : {}),
        async onReady() {
            renderPreview(initialData && initialData.blocks ? initialData : { blocks: [] });
            const data = await editor.save();
            savedSnapshot = snapshotEditorData(data);
            baselineReady = true;
            setDirty(false);
            attachVideoDeleteButtons();
        },
        async onChange() {
            const data = await editor.save();
            clearTimeout(previewDebounce);
            previewDebounce = setTimeout(() => {
                renderPreview(data);
                if (baselineReady) {
                    setDirty(snapshotEditorData(data) !== savedSnapshot);
                }
                attachVideoDeleteButtons();
            }, 100);
        },
    });

    /* Подстраховка: при изменениях DOM (например, добавили новый видео-блок) — навешиваем кнопку. */
    if (editorHolder && 'MutationObserver' in window) {
        const observer = new MutationObserver(() => attachVideoDeleteButtons());
        observer.observe(editorHolder, { childList: true, subtree: true });
    }

    document.getElementById('save-btn').addEventListener('click', async () => {
        statusNode.textContent = 'Сохранение...';
        try {
            const output = await editor.save();
            const response = await fetch('{{ route('admin.news.studio.update', $news) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    locale: '{{ $locale }}',
                    content: output,
                }),
            });

            if (!response.ok) {
                const errText = await response.text();
                throw new Error(errText);
            }

            savedSnapshot = snapshotEditorData(output);
            setDirty(false);
            statusNode.textContent = 'Сохранено';
        } catch (e) {
            statusNode.textContent = 'Ошибка сохранения. Открой консоль.';
            console.error(e);
        }
    });

    function attachStudioTranslateButton(buttonId, url, loadingText) {
        const btn = document.getElementById(buttonId);
        if (!btn) {
            return;
        }
        btn.addEventListener('click', async () => {
            if (isTranslating) {
                return;
            }
            isTranslating = true;
            btn.disabled = true;
            statusNode.textContent = loadingText;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    const message = await response.text();
                    throw new Error(message);
                }

                const result = await response.json();
                const payload = result.content;

                if (payload && Array.isArray(payload.blocks)) {
                    await editor.isReady;
                    if (typeof editor.render === 'function') {
                        await editor.render(payload);
                        renderPreview(payload);
                        const data = await editor.save();
                        savedSnapshot = snapshotEditorData(data);
                        setDirty(false);
                    } else {
                        window.location.reload();

                        return;
                    }
                } else {
                    window.location.reload();

                    return;
                }

                statusNode.textContent = result.message || 'Готово.';
            } catch (e) {
                statusNode.textContent = 'Ошибка перевода (таймаут или сеть). Попробуйте ещё раз. Подробности в консоли.';
                console.error(e);
            } finally {
                isTranslating = false;
                btn.disabled = false;
            }
        });
    }

    attachStudioTranslateButton(
        'translate-en-btn',
        '{{ route('admin.news.studio.translate-en', $news) }}',
        'Перевод RU → EN (это может занять минуту)...',
    );
    @if($locale === 'tuv' && ! empty($enableTuvStudioTranslation))
    attachStudioTranslateButton(
        'translate-tuv-btn',
        '{{ route('admin.news.studio.translate-tuv', $news) }}',
        'Перевод RU → TUV (это может занять минуту)...',
    );
    @endif
</script>
</body>
</html>

