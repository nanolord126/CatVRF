<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources;

use App\Domains\Beauty\Models\BookingSlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class BookingSlotResource extends Resource
{
    protected static ?string $model = BookingSlot::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Бронирования слотов';

    protected static ?string $modelLabel = 'Слот бронирования';

    protected static ?string $pluralModelLabel = 'Слоты бронирования';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Beauty';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('salon_id')
                            ->relationship('salon', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('master_id')
                            ->relationship('master', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'id')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Время слота')
                    ->schema([
                        Forms\Components\DatePicker::make('slot_date')
                            ->required()
                            ->native(false),
                        Forms\Components\TimePicker::make('slot_time')
                            ->required()
                            ->seconds(false),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->required()
                            ->numeric()
                            ->default(30)
                            ->minValue(15)
                            ->maxValue(480),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Статус и резервы')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'available' => 'Доступен',
                                'held' => 'Зарезервирован',
                                'booked' => 'Забронирован',
                                'cancelled' => 'Отменён',
                                'expired' => 'Истёк',
                            ])
                            ->required()
                            ->default('available'),
                        Forms\Components\DateTimePicker::make('held_at')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('booked_at')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('released_at')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Метаданные')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->reorderable()
                            ->addable()
                            ->deletable(),
                        Forms\Components\TagsInput::make('tags')
                            ->separator(','),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Системная информация')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('correlation_id')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('salon.name')
                    ->label('Салон')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('master.name')
                    ->label('Мастер')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Услуга')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slot_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('slot_time')
                    ->label('Время')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Длительность (мин)')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'held',
                        'primary' => 'booked',
                        'danger' => 'cancelled',
                        'gray' => 'expired',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Доступен',
                        'held' => 'Зарезервирован',
                        'booked' => 'Забронирован',
                        'cancelled' => 'Отменён',
                        'expired' => 'Истёк',
                    }),
                Tables\Columns\TextColumn::make('held_at')
                    ->label('Зарезервирован')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Истекает')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Доступен',
                        'held' => 'Зарезервирован',
                        'booked' => 'Забронирован',
                        'cancelled' => 'Отменён',
                        'expired' => 'Истёк',
                    ]),
                Tables\Filters\SelectFilter::make('salon')
                    ->relationship('salon', 'name'),
                Tables\Filters\SelectFilter::make('master')
                    ->relationship('master', 'name'),
                Tables\Filters\Filter::make('slot_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('slot_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('slot_date', '<=', $date),
                            );
                    }),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('slot_date', 'desc')
            ->emptyStateHeading('Нет слотов бронирования')
            ->emptyStateDescription('Создайте новый слот бронирования или измените фильтры.');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Beauty\Filament\Resources\BookingSlotResource\Pages\ListBookingSlots::route('/'),
            'create' => \App\Domains\Beauty\Filament\Resources\BookingSlotResource\Pages\CreateBookingSlot::route('/create'),
            'view' => \App\Domains\Beauty\Filament\Resources\BookingSlotResource\Pages\ViewBookingSlot::route('/{record}'),
            'edit' => \App\Domains\Beauty\Filament\Resources\BookingSlotResource\Pages\EditBookingSlot::route('/{record}/edit'),
        ];
    }
}
