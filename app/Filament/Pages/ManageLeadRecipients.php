<?php

namespace App\Filament\Pages;

use App\Services\Leads\LeadRecipientList;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Получатели писем по заявкам «Интерес к району» с карты на главной.
 */
class ManageLeadRecipients extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static ?string $navigationLabel = 'Заявки с карты';

    protected static ?string $title = 'Получатели заявок';

    protected static string|UnitEnum|null $navigationGroup = 'Контент лендинга';

    protected static ?int $navigationSort = 25;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $slug = 'landing/lead-recipients';

    protected string $view = 'filament-panels::pages.page';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('landing.update') ?? false;
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $emails = LeadRecipientList::resolve();

        $this->form->fill([
            'recipient_emails' => array_map(
                static fn (string $email): array => ['email' => $email],
                $emails !== [] ? $emails : [''],
            ),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Куда приходят заявки')
                    ->description('Письма отправляются при отправке формы «Интерес к району» на карте главной страницы. Можно указать несколько адресов — Gmail, Яндекс и др.')
                    ->schema([
                        Repeater::make('recipient_emails')
                            ->label('E-mail получателей')
                            ->schema([
                                TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('manager@gmail.com'),
                            ])
                            ->addActionLabel('Добавить получателя')
                            ->reorderable(false)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->itemLabel(fn (array $state): ?string => filled($state['email'] ?? null)
                                ? (string) $state['email']
                                : null),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment($this->getFormActionsAlignment())
                            ->key('form-actions'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $rows = is_array($state['recipient_emails'] ?? null) ? $state['recipient_emails'] : [];
        $emails = LeadRecipientList::normalize($rows);

        if ($emails === []) {
            Notification::make()
                ->title('Укажите хотя бы один корректный e-mail')
                ->danger()
                ->send();

            return;
        }

        LeadRecipientList::store($emails);

        Notification::make()
            ->title('Сохранено')
            ->body('Получатели заявок обновлены.')
            ->success()
            ->send();

        $this->fillForm();
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Сохранить')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Start;
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->icon('heroicon-o-check-circle'),
        ];
    }
}
