<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            editor: null,
            ready: false,
            failed: false,
            errorMessage: '',
            toolsLoaded: false,
            async ensureScripts() {
                if (window.EditorJS && window.Header && (window.List || window.EditorjsList) && window.Quote) {
                    this.toolsLoaded = true
                    return
                }

                const load = (src) => new Promise((resolve, reject) => {
                    const existing = document.querySelector(`script[src='${src}']`)
                    if (existing) {
                        existing.addEventListener('load', resolve, { once: true })
                        if (existing.dataset.loaded === '1') resolve()
                        return
                    }

                    const script = document.createElement('script')
                    script.src = src
                    script.async = true
                    script.onload = () => {
                        script.dataset.loaded = '1'
                        resolve()
                    }
                    script.onerror = reject
                    document.head.appendChild(script)
                })

                await load('https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest')
                await load('https://cdn.jsdelivr.net/npm/@editorjs/header@latest')
                await load('https://cdn.jsdelivr.net/npm/@editorjs/list@latest')
                await load('https://cdn.jsdelivr.net/npm/@editorjs/quote@latest')
                try {
                    await load('https://cdn.jsdelivr.net/npm/@editorjs/embed@2.7.6/dist/embed.umd.js')
                } catch (e) {
                    console.warn('EditorJS Embed (video) failed to load', e)
                }

                await load("{{ asset('js/editorjs-i18n-ru.js') }}")

                this.toolsLoaded = true
            },
            normalizeState(rawState) {
                if (typeof rawState === 'string') {
                    try {
                        const parsed = JSON.parse(rawState)
                        rawState = parsed
                    } catch (e) {
                        return { blocks: [] }
                    }
                }

                if (!rawState || typeof rawState !== 'object') {
                    return { blocks: [] }
                }

                if (Array.isArray(rawState)) {
                    return { blocks: rawState }
                }

                if (!Array.isArray(rawState.blocks)) {
                    rawState.blocks = []
                }

                return rawState
            },
            async initEditor() {
                try {
                    await this.ensureScripts()

                    const holderId = 'editorjs-{{ str($getStatePath())->slug('-') }}'
                    const initialData = this.normalizeState(this.state)
                    const listTool = window.List || window.EditorjsList

                    const tools = {
                        header: {
                            class: Header,
                            config: {
                                levels: [2, 3, 4],
                                defaultLevel: 2,
                            },
                        },
                        list: {
                            class: listTool,
                            inlineToolbar: true,
                        },
                        quote: {
                            class: Quote,
                            inlineToolbar: true,
                        },
                    }
                    if (window.Embed) {
                        tools.embed = {
                            class: window.Embed,
                            config: {
                                services: {
                                    youtube: true,
                                    vimeo: true,
                                },
                            },
                        }
                    }

                    this.editor = new EditorJS({
                        holder: holderId,
                        placeholder: 'Начните писать новость...',
                        data: initialData,
                        minHeight: 220,
                        tools,
                        ...(window.editorJsI18nRu ? { i18n: window.editorJsI18nRu } : {}),
                        onChange: async () => {
                            const output = await this.editor.save()
                            this.state = output
                        },
                    })

                    this.ready = true
                } catch (error) {
                    this.failed = true
                    this.errorMessage = (error && error.message) ? error.message : 'Не удалось загрузить Editor.js'
                }
            },
            updateFallback(value) {
                try {
                    this.state = JSON.parse(value)
                } catch (e) {
                    // Keep previous state until JSON becomes valid.
                }
            },
        }"
        x-init="initEditor()"
        class="rounded-xl border border-gray-300 p-3 dark:border-gray-700"
    >
        <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
            Блоковый редактор: заголовки, списки, цитаты, видео (Embed — ссылка YouTube или Vimeo). Контент сохранится в JSON автоматически.
        </div>

        <div
            wire:ignore
            id="editorjs-{{ str($getStatePath())->slug('-') }}"
            class="prose dark:prose-invert max-w-none min-h-[220px]"
            x-show="!failed"
        ></div>

        <div x-show="failed" class="space-y-2">
            <div class="text-xs text-danger-600 dark:text-danger-400">
                Editor.js временно недоступен: <span x-text="errorMessage"></span>
            </div>
            <textarea
                class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"
                rows="10"
                :value="JSON.stringify(normalizeState(state), null, 2)"
                @input="updateFallback($event.target.value)"
            ></textarea>
        </div>
    </div>
</x-dynamic-component>

