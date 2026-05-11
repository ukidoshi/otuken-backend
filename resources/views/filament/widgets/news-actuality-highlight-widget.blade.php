<x-filament-widgets::widget class="fi-wi-news-actuality-highlight">
    <x-filament::section heading="Закрепленная новость">
        <form wire:submit="save" class="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-4">
            <div class="w-full min-w-0 sm:flex-1">
                @php
                    $chevronSvg = rawurlencode(
                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#64748b">'
                        . '<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>'
                        . '</svg>'
                    );
                    $selectChevronStyle = sprintf(
                        'min-height:3.25rem;appearance:none;-webkit-appearance:none;-moz-appearance:none;'
                        . 'background-image:url("data:image/svg+xml,%s");background-repeat:no-repeat;'
                        . 'background-position:right 0.75rem center;background-size:1.25rem;padding-right:2.75rem;',
                        $chevronSvg
                    );
                @endphp
                <select
                    id="news-actuality-select"
                    wire:model="newsId"
                    aria-label="Новость для закрепления на главной странице"
                    style="{{ $selectChevronStyle }}"
                    class="w-full cursor-pointer appearance-none rounded-2xl border-2 border-primary-600 bg-white px-4 py-3 text-base font-semibold text-gray-950 shadow-md transition-shadow hover:shadow-lg focus:shadow-lg focus:outline-none focus:ring-4 focus:ring-primary-500/30 dark:border-primary-400 dark:bg-gray-950 dark:text-white dark:focus:ring-primary-400/25 sm:text-sm"
                >
                    <option value="">— Нет закрепления —</option>
                    @foreach ($this->newsOptions as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('newsId')
                    <p class="mt-2 text-sm font-medium text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full shrink-0 sm:w-44">
                <x-filament::button
                    type="submit"
                    wire:loading.attr="disabled"
                    size="lg"
                    class="w-full"
                >
                    Сохранить
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
