<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NewsStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Hidden = 'hidden';
    case Archived = 'archived';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Scheduled => 'Запланировано',
            self::Published => 'Опубликовано',
            self::Hidden => 'Скрыто',
            self::Archived => 'В архиве',
        };
    }
}
