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
                Forms\Components\Section::make('Детали записи')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->default(fn () => (string) Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('salon_id')
                            ->label('Салон')
                            ->relationship('salon', 'name', fn (Builder $query) => $query->where('tenant_id', tenant('id')))
                            ->required()
                            ->reactive()
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
                            ->required(),
                        Forms\Components\DateTimePicker::make('datetime_start')
                            ->label('Дата и время начала')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y H:i'),
                        Forms\Components\TextInput::make('price')
                            ->label('Цена (копейки)')
                            ->numeric()
                            ->required()
                            ->prefix('₽'),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает',
                                'confirmed' => 'Подтверждена',
                                'completed' => 'Выполнена',
                                'cancelled' => 'Отменена',
                            ])
                            ->default('pending')
                            ->required(),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('master.full_name')
                    ->label('Мастер')
                    ->searchable(),
                Tables\Columns\TextColumn::make('salon.name')
                    ->label('Салон'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB', locale: 'ru_RU', divideBy: 100),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждена',
                        'completed' => 'Выполнена',
                        'cancelled' => 'Отменена',
                    ]),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Оплата'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ожидает',
                        'confirmed' => 'Подтверждена',
                        'completed' => 'Выполнена',
                        'cancelled' => 'Отменена',
                    ]),
                Tables\Filters\Filter::make('datetime_start')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
