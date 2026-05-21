<?php

namespace App\Filament\Concerns;

/**
 * Дублирует «Сохранить» в шапке страницы — всегда под рукой на длинных формах.
 */
trait HasHeaderSaveAction
{
    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Сохранить')
                ->icon('heroicon-o-check-circle'),
        ];
    }
}
