<?php

namespace App\Filament\Resources\User\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Профиль')
                    ->schema([
                        TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->revealable()
                            ->live(onBlur: true)
                            ->rules([Password::defaults()], fn (Get $get): bool => filled($get('password')))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Оставьте пустым, чтобы не менять пароль. Если сотрудник забыл пароль — задайте новый и сообщите ему.'
                                : ''),
                        TextInput::make('passwordConfirmation')
                            ->label('Подтверждение пароля')
                            ->password()
                            ->revealable()
                            ->same('password')
                            ->required(fn (string $operation, Get $get): bool => $operation === 'create' || filled($get('password')))
                            ->dehydrated(false)
                            ->visible(fn (string $operation, Get $get): bool => $operation === 'create' || filled($get('password'))),
                        Select::make('role')
                            ->label('Роль')
                            ->options([
                                'user' => 'Пользователь',
                                'admin' => 'Администратор',
                            ])
                            ->default('user')
                            ->required()
                            ->native(false)
                            ->helperText('Новым сотрудникам обычно назначают «Пользователь». Администратор может добавлять пользователей.'),
                    ]),
            ]);
    }
}
