<x-filament-panels::page>
    <div class="fi-prose dark:prose-invert max-w-none space-y-2 text-sm text-gray-600 dark:text-gray-400">
        <p>
            Разделы ниже совпадают с логикой публичного сайта. Для быстрого старта откройте нужный блок —
            формы содержат подсказки «где это на сайте».
        </p>
    </div>

    <ul class="mt-6 grid gap-4 sm:grid-cols-2">
        @foreach ($this->sections as $section)
            <li
                class="flex flex-col justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10"
            >
                <div class="space-y-2">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ $section['title'] }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $section['admin_hint'] }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        <span class="font-medium text-gray-700 dark:text-gray-300">На сайте:</span>
                        {{ $section['site_path'] }}
                    </p>
                </div>
                <div>
                    <x-filament::button
                        tag="a"
                        :href="$section['href']"
                        icon="heroicon-o-arrow-top-right-on-square"
                        icon-position="after"
                    >
                        {{ $section['action'] }}
                    </x-filament::button>
                </div>
            </li>
        @endforeach
    </ul>
</x-filament-panels::page>
