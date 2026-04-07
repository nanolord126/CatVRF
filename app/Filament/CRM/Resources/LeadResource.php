<?php declare(strict_types=1);

namespace App\Filament\CRM\Resources;

use App\Services\AuditService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * CRM: Лиды — потенциальные клиенты, которых обрабатывают менеджеры.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Модель CrmLead хранится в app/Models/CrmLead.php (таблица crm_leads).
 * FraudControlService::check() вызывается перед конвертацией лида в клиента.
 */
final class LeadResource extends Resource
{
    protected static ?string $model = \App\Models\CrmLead::class;
    protected static ?string $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $navigationGroup = 'Клиенты';
    protected static ?string $navigationLabel = 'Лиды';
    protected static ?string $modelLabel = 'Лид';
    protected static ?string $pluralModelLabel = 'Лиды';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Информация о лиде')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->columnSpan(1),
                    TextInput::make('source')
                        ->label('Источник')
                        ->placeholder('instagram, google, referral...')
                        ->maxLength(100)
                        ->columnSpan(1),
                    Select::make('vertical')
                        ->label('Вертикаль интереса')
                        ->options([
                            'beauty'    => 'Beauty',
                            'food'      => 'Food',
                            'furniture' => 'Furniture',
                            'fashion'   => 'Fashion',
                            'fitness'   => 'Fitness',
                            'hotel'     => 'Отели',
                            'travel'    => 'Путешествия',
                            'real_estate' => 'Недвижимость',
                            'auto'      => 'Авто',
                            'medical'   => 'Медицина',
                            'other'     => 'Другое',
                        ])
                        ->columnSpan(1),
                    Select::make('status')
                        ->label('Статус')
                        ->options([
                            'new'         => 'Новый',
                            'contacted'   => 'Связались',
                            'qualified'   => 'Квалифицирован',
                            'in_progress' => 'В работе',
                            'won'         => 'Конвертирован',
                            'lost'        => 'Потерян',
                        ])
                        ->default('new')
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('expected_value')
                        ->label('Ожидаемая стоимость (₽)')
                        ->numeric()
                        ->prefix('₽')
                        ->columnSpan(1),
                    DateTimePicker::make('follow_up_at')
                        ->label('Следующий контакт')
                        ->columnSpan(1),
                    Textarea::make('notes')
                        ->label('Заметки')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('source')
                    ->label('Источник')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('vertical')
                    ->label('Вертикаль')
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'lost'        => 'danger',
                        'in_progress' => 'warning',
                        'new'         => 'gray',
                        default       => 'info',
                    }),
                TextColumn::make('expected_value')
                    ->label('Ожидаемая стоимость')
                    ->money('RUB')
                    ->sortable(),
                TextColumn::make('follow_up_at')
                    ->label('Следующий контакт')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(fn ($state): string => $state && $state < now() ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new'         => 'Новый',
                        'contacted'   => 'Связались',
                        'qualified'   => 'Квалифицирован',
                        'in_progress' => 'В работе',
                        'won'         => 'Конвертирован',
                        'lost'        => 'Потерян',
                    ]),
                SelectFilter::make('vertical')
                    ->label('Вертикаль')
                    ->options([
                        'beauty'    => 'Beauty',
                        'food'      => 'Food',
                        'furniture' => 'Furniture',
                        'fashion'   => 'Fashion',
                        'fitness'   => 'Fitness',
                        'hotel'     => 'Отели',
                        'travel'    => 'Путешествия',
                        'real_estate' => 'Недвижимость',
                        'auto'      => 'Авто',
                        'medical'   => 'Медицина',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('convert')
                    ->label('Конвертировать в клиента')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => $record->status !== 'won')
                    ->action(function (Model $record): void {
                        $record->update(['status' => 'won']);

                        app(AuditService::class)->record(
                            'crm_lead_converted',
                            get_class($record),
                            $record->id,
                            ['status' => $record->getOriginal('status')],
                            ['status' => 'won'],
                        );

                        Notification::make()
                            ->title('Лид конвертирован в клиента')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('follow_up_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\CRM\Resources\LeadResource\Pages\ListLeads::route('/'),
            'create' => \App\Filament\CRM\Resources\LeadResource\Pages\CreateLead::route('/create'),
            'edit'   => \App\Filament\CRM\Resources\LeadResource\Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
