<?php declare(strict_types=1);

namespace App\Filament\Emergency\Resources;

use App\Services\AuditService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Emergency Panel: управление вызовами экстренных служб.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Отображает активные/завершённые вызовы экстренных служб
 * и позволяет диспетчеру назначать ресурсы.
 */
final class EmergencyCallResource extends Resource
{
    protected static ?string $model = \App\Models\EmergencyCall::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationGroup = 'Диспетчеризация';
    protected static ?string $navigationLabel = 'Вызовы';
    protected static ?string $modelLabel = 'Вызов';
    protected static ?string $pluralModelLabel = 'Вызовы';
    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Информация о вызове')
                ->columns(2)
                ->schema([
                    TextInput::make('caller_name')
                        ->label('Звонящий')
                        ->disabled()
                        ->columnSpan(1),
                    TextInput::make('caller_phone')
                        ->label('Телефон')
                        ->disabled()
                        ->copyable()
                        ->columnSpan(1),
                    TextInput::make('address')
                        ->label('Адрес')
                        ->disabled()
                        ->columnSpanFull(),
                    TextInput::make('category')
                        ->label('Категория')
                        ->disabled()
                        ->columnSpan(1),
                    Select::make('status')
                        ->label('Статус')
                        ->options([
                            'new'        => 'Новый',
                            'dispatched' => 'Направлен экипаж',
                            'on_scene'   => 'На месте',
                            'resolved'   => 'Завершён',
                            'cancelled'  => 'Отменён',
                            'false_call' => 'Ложный вызов',
                        ])
                        ->columnSpan(1),
                    TextInput::make('assigned_unit')
                        ->label('Назначенный экипаж')
                        ->columnSpan(1),
                    Textarea::make('dispatcher_notes')
                        ->label('Заметки диспетчера')
                        ->rows(3)
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
                    ->sortable(),
                TextColumn::make('caller_phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('category')
                    ->label('Категория')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'medical'  => 'warning',
                        'accident' => 'danger',
                        'crime'    => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dispatched' => 'warning',
                        'on_scene'   => 'info',
                        'resolved'   => 'success',
                        'false_call' => 'gray',
                        'cancelled'  => 'gray',
                        default      => 'gray',
                    }),
                TextColumn::make('assigned_unit')
                    ->label('Экипаж')
                    ->default('—'),
                TextColumn::make('created_at')
                    ->label('Время вызова')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new'        => 'Новый',
                        'dispatched' => 'Направлен экипаж',
                        'on_scene'   => 'На месте',
                        'resolved'   => 'Завершён',
                        'cancelled'  => 'Отменён',
                        'false_call' => 'Ложный вызов',
                    ]),
                SelectFilter::make('category')
                    ->label('Категория')
                    ->options([
                        'fire'     => 'Пожар',
                        'medical'  => 'Медицина',
                        'accident' => 'ДТП',
                        'crime'    => 'Преступление',
                        'other'    => 'Другое',
                    ]),
                Filter::make('active')
                    ->label('Активные')
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', ['new', 'dispatched', 'on_scene'])),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('dispatch')
                    ->label('Направить экипаж')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        TextInput::make('unit')
                            ->label('Номер экипажа')
                            ->required()
                            ->maxLength(20),
                    ])
                    ->visible(fn (Model $record): bool => $record->status === 'new')
                    ->action(function (Model $record, array $data): void {
                        $record->update([
                            'status'        => 'dispatched',
                            'assigned_unit' => $data['unit'],
                        ]);

                        app(AuditService::class)->record(
                            'emergency_dispatched',
                            get_class($record),
                            $record->id,
                            ['status' => 'new'],
                            ['status' => 'dispatched', 'unit' => $data['unit']],
                        );

                        Notification::make()
                            ->title('Экипаж ' . $data['unit'] . ' направлен')
                            ->success()
                            ->send();
                    }),
                Action::make('resolve')
                    ->label('Завершить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => in_array($record->status, ['dispatched', 'on_scene']))
                    ->action(function (Model $record): void {
                        $record->update(['status' => 'resolved']);

                        app(AuditService::class)->record(
                            'emergency_resolved',
                            get_class($record),
                            $record->id,
                            ['status' => $record->getOriginal('status')],
                            ['status' => 'resolved'],
                        );

                        Notification::make()
                            ->title('Вызов завершён')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('5s'); // авто-обновление каждые 5 секунд
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Emergency\Resources\EmergencyCallResource\Pages\ListEmergencyCalls::route('/'),
            'view'  => \App\Filament\Emergency\Resources\EmergencyCallResource\Pages\ViewEmergencyCall::route('/{record}'),
        ];
    }
}
