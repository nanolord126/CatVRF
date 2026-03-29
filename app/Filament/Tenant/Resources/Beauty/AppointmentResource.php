<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\BeautySalon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Appointment Resource (Layer 7)
 * 
 * Управление записями: выбор мастера, салона, статуса, цены и Tenant Scoping.
 */
final class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Записи на услуги';
    protected static ?string $navigationGroup = 'Beauty & Wellness';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Клиент')
                    ->description('Информация о клиенте')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->default(fn () => (string) Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'name', fn (Builder $query) => $query->where('tenant_id', tenant('id')))
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('client_phone')
                            ->label('Телефон клиента')
                            ->tel()
                            ->nullable(),
                        Forms\Components\TextInput::make('client_email')
                            ->label('Email клиента')
                            ->email()
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Детали услуги')
                    ->description('Выбор салона, мастера и услуги')
                    ->schema([
                        Forms\Components\Select::make('salon_id')
                            ->label('Салон')
                            ->relationship('salon', 'name', fn (Builder $query) => $query->where('tenant_id', tenant('id')))
                            ->required()
                            ->reactive()
                            ->preload()
                            ->afterStateUpdated(fn (callable $set) => $set('master_id', null)),
                        Forms\Components\Select::make('master_id')
                            ->label('Мастер')
                            ->relationship('master', 'full_name', function (Builder $query, callable $get) {
                                $salonId = $get('salon_id');
                                $query->where('tenant_id', tenant('id'));
                                if ($salonId) {
                                    $query->where('salon_id', $salonId);
                                }
                                return $query;
                            })
                            ->required()
                            ->reactive()
                            ->preload(),
                        Forms\Components\Select::make('service_id')
                            ->label('Услуга')
                            ->relationship('service', 'name', function (Builder $query, callable $get) {
                                $masterId = $get('master_id');
                                if ($masterId) {
                                    $query->whereHas('master', fn ($q) => $q->where('master_id', $masterId));
                                }
                                return $query;
                            })
                            ->nullable(),
                        Forms\Components\TextInput::make('service_name')
                            ->label('Название услуги')
                            ->nullable()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('График и время')
                    ->description('Дата и время проведения')
                    ->schema([
                        Forms\Components\DateTimePicker::make('datetime_start')
                            ->label('Дата и время начала')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y H:i'),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Длительность (мин)')
                            ->numeric()
                            ->nullable()
                            ->default(60),
                    ])->columns(2),

                Forms\Components\Section::make('Финансы')
                    ->description('Цена и оплата')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Цена (рубли)')
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->prefix('₽'),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Скидка (копейки)')
                            ->numeric()
                            ->default(0)
                            ->nullable(),
                        Forms\Components\TextInput::make('total_price')
                            ->label('Итого (копейки)')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\Select::make('payment_status')
                            ->label('Статус оплаты')
                            ->options([
                                'pending' => 'Ожидает',
                                'paid' => 'Оплачена',
                                'failed' => 'Ошибка',
                                'refunded' => 'Возвращена',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Статус')
                    ->description('Состояние записи')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус записи')
                            ->options([
                                'pending' => 'Ожидает подтверждения',
                                'confirmed' => 'Подтверждена',
                                'completed' => 'Выполнена',
                                'cancelled' => 'Отменена',
                                'no_show' => 'Не явился',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\TextInput::make('cancellation_reason')
                            ->label('Причина отмены')
                            ->nullable()
                            ->maxLength(500),
                        Forms\Components\RichEditor::make('notes')
                            ->label('Заметки')
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => tenant('id')),
                Forms\Components\Hidden::make('correlation_id')
                    ->default(fn () => (string) Str::uuid()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('datetime_start')
                    ->label('Время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('master.full_name')
                    ->label('Мастер')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salon.name')
                    ->label('Салон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client_phone')
                    ->label('Клиент')
                    ->copyable()
                    ->icon('heroicon-o-phone'),
                Tables\Columns\TextColumn::make('service_name')
                    ->label('Услуга'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Цена')
                    ->money('RUB', locale: 'ru_RU', divideBy: 100)
                    ->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждена',
                        'completed' => 'Выполнена',
                        'cancelled' => 'Отменена',
                        'no_show' => 'Не явился',
                    ]),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Оплата')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'pending',
                        'danger' => 'failed',
                        'info' => 'refunded',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждена',
                        'completed' => 'Выполнена',
                        'cancelled' => 'Отменена',
                        'no_show' => 'Не явился',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Статус оплаты')
                    ->options([
                        'pending' => 'Ожидает',
                        'paid' => 'Оплачена',
                        'failed' => 'Ошибка',
                        'refunded' => 'Возвращена',
                    ]),
                Tables\Filters\SelectFilter::make('master_id')
                    ->label('Мастер')
                    ->relationship('master', 'full_name', fn (Builder $query) => $query->where('tenant_id', tenant('id'))),
                Tables\Filters\Filter::make('datetime_start')
                    ->label('Дата записи')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('С'),
                        Forms\Components\DatePicker::make('until')->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date): Builder => $query->whereDate('datetime_start', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date): Builder => $query->whereDate('datetime_start', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('confirm')
                        ->label('Подтвердить')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['status' => 'confirmed'])),
                    Tables\Actions\BulkAction::make('cancel')
                        ->label('Отменить')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['status' => 'cancelled'])),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant('id'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'view' => Pages\ViewAppointment::route('/{record}'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
