<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventPlanning;

use App\Domains\EventPlanning\Models\Event;
use App\Filament\Tenant\Resources\EventPlanning\EventResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Filament Resource EventResource.
 * Канон 2026: Multi-tenancy, Audit-logs, Fraud Check, glassmorphism UI.
 * Управление событиями и праздниками в ЛК Тенета.
 */
final class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Event Planning Management';
    protected static ?string $modelLabel = 'Событие';
    protected static ?string $pluralModelLabel = 'События и Праздники';

    /**
     * Форма создания/редактирования события.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация (Core Info)')
                    ->description('Базовые параметры планируемого события')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->minLength(5)
                            ->maxLength(255)
                            ->label('Название события'),

                        Select::make('type')
                            ->required()
                            ->options([
                                'wedding' => 'Свадьба',
                                'corporate' => 'Корпоратив',
                                'birthday' => 'День рождения',
                                'anniversary' => 'Юбилей',
                                'other' => 'Другое',
                            ])
                            ->label('Тип'),

                        DateTimePicker::make('event_date')
                            ->required()
                            ->label('Дата и время'),

                        TextInput::make('location')
                            ->required()
                            ->prefix('📍')
                            ->label('Место проведения'),

                        TextInput::make('guest_count')
                            ->numeric()
                            ->minValue(1)
                            ->default(20)
                            ->label('Количество гостей'),

                        Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Черновик',
                                'planning' => 'Планирование',
                                'confirmed' => 'Подтверждено',
                                'active' => 'В процессе',
                                'completed' => 'Завершено',
                                'cancelled' => 'Отменено',
                            ])
                            ->default('draft')
                            ->label('Статус'),
                    ]),

                Section::make('Финансы и Бюджет (Financial Control)')
                    ->description('Контроль финансового потока праздника')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_budget_kopecks')
                            ->numeric()
                            ->label('Бюджет (в копейках)')
                            ->helperText('Сумма в копейках для точности расчетов (int 2026)'),

                        TextInput::make('prepayment_kopecks')
                            ->numeric()
                            ->disabled()
                            ->label('Требуемая предоплата (коп.)'),

                        TextInput::make('cancellation_fee_kopecks')
                            ->numeric()
                            ->label('Штраф за отмену (коп.)'),
                    ]),

                Section::make('AI Протокол (AI Constructor)')
                    ->schema([
                        Forms\Components\KeyValue::make('ai_plan')
                            ->label('AI Детализация плана')
                            ->disabled(),
                    ]),
            ]);
    }

    /**
     * Таблица списка событий.
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->label('Тип')
                    ->color(fn (string $state): string => match ($state) {
                        'wedding' => 'pink',
                        'corporate' => 'indigo',
                        'birthday' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('event_date')
                    ->label('Дата')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('guest_count')
                    ->label('Гостей')
                    ->numeric(),

                TextColumn::make('status')
                    ->badge()
                    ->label('Статус'),

                TextColumn::make('total_budget_kopecks')
                    ->label('Бюджет')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'wedding' => 'Свадьба',
                        'corporate' => 'Корпоратив',
                        'birthday' => 'День рождения',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->before(function (Event $record, array $data) {
                        Log::channel('audit')->info('Filament: Editing event', [
                            'event_uuid' => $record->uuid,
                            'tenant_id' => $record->tenant_id,
                            'user_id' => auth()->id(),
                        ]);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('План обновлен')
                            ->body('Все изменения в бюджете и вендорах зафиксированы.')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Глобальный поиск и запрос по тененам.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
