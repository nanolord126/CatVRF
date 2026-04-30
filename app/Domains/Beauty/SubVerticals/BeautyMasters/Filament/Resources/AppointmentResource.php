<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\BeautyMasters\Infrastructure\Models\AppointmentModel;
use Modules\BeautyMasters\Infrastructure\Models\MasterModel;
use Modules\BeautyMasters\Infrastructure\Models\ClientModel;
use Modules\BeautyMasters\Infrastructure\Models\ServiceModel;
use Modules\BeautyMasters\Domain\Entities\AppointmentStatus;

final class AppointmentResource extends Resource
{
    protected static ?string $model = AppointmentModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Записи';

    protected static ?string $modelLabel = 'Запись';

    protected static ?string $pluralModelLabel = 'Записи';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о записи')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->label('Салон')
                            ->relationship('venue', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('master_id')
                            ->label('Мастер')
                            ->relationship('master', 'last_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getSearchResultsUsing(fn (string $search) =>
                                MasterModel::where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('full_name', 'id')
                            ),

                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'last_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getSearchResultsUsing(fn (string $search) =>
                                ClientModel::where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('full_name', 'id')
                            ),

                        Forms\Components\Select::make('service_id')
                            ->label('Услуга')
                            ->relationship('service', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Components\Select $component, $state) =>  
                                $component->getContainer()->getComponent('duration_minutes')?->fill(
                                    Modules\BeautyMasters\Infrastructure\Models\Modules\BeautyMasters\Infrastructure\Models\ServiceModel::find($state)?->duration_minutes ?? 30
                                )
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Дата и время')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->label('Начало')
                            ->required()
                            ->seconds(false)
                            ->live()
                            ->afterStateUpdated(function (Forms\Components\DateTimePicker $component, $state, Forms\Components\Select $durationSelect) {
                                if ($state && $durationSelect) {
                                    $duration = $durationSelect->getState() ?? 30;
                                    $endTime = \Carbon\Carbon::parse($state)->addMinutes($duration);
                                    $component->getContainer()->getComponent('end_time')?->fill($endTime);
                                }
                            }),

                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('Окончание')
                            ->required()
                            ->seconds(false),

                        Forms\Components\Hidden::make('duration_minutes')
                            ->default(30),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Статус и оплата')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                AppointmentStatus::PENDING => 'Ожидает подтверждения',
                                AppointmentStatus::CONFIRMED => 'Подтверждена',
                                AppointmentStatus::IN_PROGRESS => 'В процессе',
                                AppointmentStatus::COMPLETED => 'Завершена',
                                AppointmentStatus::CANCELLED => 'Отменена',
                                AppointmentStatus::NO_SHOW => 'Неявка',
                                AppointmentStatus::PAID => 'Оплачена',
                            ])
                            ->required()
                            ->default(AppointmentStatus::PENDING),

                        Forms\Components\Select::make('payment_status')
                            ->label('Статус оплаты')
                            ->options([
                                'unpaid' => 'Не оплачена',
                                'partial' => 'Частично',
                                'paid' => 'Оплачена',
                                'refunded' => 'Возврат',
                            ])
                            ->required()
                            ->default('unpaid'),

                        Forms\Components\TextInput::make('price')
                            ->label('Цена')
                            ->numeric()
                            ->prefix('₽')
                            ->required(),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Скидка')
                            ->numeric()
                            ->prefix('₽')
                            ->default(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3),

                        Forms\Components\KeyValue::make('client_notes')
                            ->label('Заметки клиента')
                            ->keyLabel('Поле')
                            ->valueLabel('Значение')
                            ->reorderable(),

                        Forms\Components\Toggle::make('is_online_booking')
                            ->label('Онлайн-запись')
                            ->default(false),

                        Forms\Components\TextInput::make('booking_source')
                            ->label('Источник записи')
                            ->default('manual'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Дата и время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn (AppointmentModel $record): string => 
                        $record->service->name ?? ''
                    ),

                Tables\Columns\TextColumn::make('master.full_name')
                    ->label('Мастер')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('client.full_name')
                    ->label('Клиент')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Статус')
                    ->options([
                        AppointmentStatus::PENDING => 'Ожидает',
                        AppointmentStatus::CONFIRMED => 'Подтверждена',
                        AppointmentStatus::IN_PROGRESS => 'В процессе',
                        AppointmentStatus::COMPLETED => 'Завершена',
                        AppointmentStatus::CANCELLED => 'Отменена',
                        AppointmentStatus::NO_SHOW => 'Неявка',
                        AppointmentStatus::PAID => 'Оплачена',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Оплата')
                    ->colors([
                        'danger' => 'unpaid',
                        'warning' => 'partial',
                        'success' => 'paid',
                        'gray' => 'refunded',
                    ]),

                Tables\Columns\TextColumn::make('final_price')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),
            ])
            ->defaultSort('start_time', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        AppointmentStatus::PENDING => 'Ожидает подтверждения',
                        AppointmentStatus::CONFIRMED => 'Подтверждена',
                        AppointmentStatus::IN_PROGRESS => 'В процессе',
                        AppointmentStatus::COMPLETED => 'Завершена',
                        AppointmentStatus::CANCELLED => 'Отменена',
                        AppointmentStatus::NO_SHOW => 'Неявка',
                        AppointmentStatus::PAID => 'Оплачена',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Статус оплаты')
                    ->options([
                        'unpaid' => 'Не оплачена',
                        'partial' => 'Частично',
                        'paid' => 'Оплачена',
                        'refunded' => 'Возврат',
                    ]),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Предстоящие')
                    ->query(fn ($query) => $query->where('start_time', '>=', now())),

                Tables\Filters\Filter::make('today')
                    ->label('Сегодня')
                    ->query(fn ($query) => $query->whereDate('start_time', today())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'photos' => Tables\Columns\TextColumn::make('photos'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\BeautyMasters\Filament\Resources\AppointmentResource\\Modules\BeautyMasters\Filament\Resources\AppointmentResource\Pages\ListAppointments::route('/'),
            'create' => \Modules\BeautyMasters\Filament\Resources\AppointmentResource\\Modules\BeautyMasters\Filament\Resources\AppointmentResource\Pages\CreateAppointment::route('/create'),
            'edit' => \Modules\BeautyMasters\Filament\Resources\AppointmentResource\\Modules\BeautyMasters\Filament\Resourceedit'),
            'calendar' => \Modules\BeautyMasters\Filament\Resources\AppointmentResource\Pages\Calsn\arVAew::roupe('/calendarpointmentResource\Pages\EditAppointment::route('/{record}/edit'),
            'calendar' => \Modules\BeautyMasters\Filament\Resources\AppointmentResource\Pages\CalendarView::route('/calendar'),
        ];
    }
}
