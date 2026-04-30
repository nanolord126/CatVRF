<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources;

use Modules\Restaurant\Models\Reservation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * BookingResource — управление бронированиями столов ресторана в Filament.
 * CatCRM Standard Color Scheme 2026 с анимациями статусов.
 */
final class BookingResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Бронирования';

    protected static ?string $pluralModelLabel = 'Бронирования';

    protected static ?string $modelLabel = 'Бронирование';

    protected static ?string $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о бронировании')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Select::make('restaurant_id')
                            ->relationship('restaurant', 'name')
                            ->label('Ресторан')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Клиент')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('party_size')
                            ->label('Количество гостей')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Дата и время')
                    ->schema([
                        Forms\Components\DatePicker::make('reservation_date')
                            ->label('Дата бронирования')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\DateTimePicker::make('reservation_time')
                            ->label('Время бронирования')
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает подтверждения',
                                'confirmed' => 'Подтверждено',
                                'arrived' => 'Гости прибыли',
                                'completed' => 'Завершено',
                                'cancelled' => 'Отменено',
                                'no_show' => 'Не пришли',
                            ])
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_confirmed')
                            ->label('Подтверждено')
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Контактная информация')
                    ->schema([
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Телефон')
                            ->tel()
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('special_requests')
                            ->label('Особые пожелания')
                            ->disabled()
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Ресторан')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('party_size')
                    ->label('Гостей')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => $state . ' чел.'),
                Tables\Columns\TextColumn::make('reservation_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reservation_time')
                    ->label('Время')
                    ->dateTime('H:i')
                    ->sortable(),
                Tables\Columns\ViewColumn::make('status')
                    ->label('Статус')
                    ->view('components.status-badge')
                    ->viewData(fn ($record): array => [
                        'status' => $record->status,
                        'label' => match ($record->status) {
                            'pending' => 'Ожидает подтверждения',
                            'confirmed' => 'Подтверждено',
                            'arrived' => 'Гости прибыли',
                            'completed' => 'Завершено',
                            'cancelled' => 'Отменено',
                            'no_show' => 'Не пришли',
                            default => ucfirst($record->status),
                        },
                        'color' => match ($record->status) {
                            'pending' => 'secondary',
                            'confirmed' => 'info',
                            'arrived' => 'success',
                            'completed' => 'emerald',
                            'cancelled' => 'danger',
                            'no_show' => 'warning',
                            default => 'secondary',
                        },
                        'icon' => match ($record->status) {
                            'pending' => 'clock',
                            'confirmed' => 'calendar',
                            'arrived' => 'user',
                            'completed' => 'check-badge',
                            'cancelled' => 'x-circle',
                            'no_show' => 'users',
                            default => null,
                        },
                    ]),
                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Подтв.')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает подтверждения',
                        'confirmed' => 'Подтверждено',
                        'arrived' => 'Гости прибыли',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                        'no_show' => 'Не пришли',
                    ]),
                Tables\Filters\Filter::make('reservation_date')
                    ->label('Дата бронирования')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('reservation_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('reservation_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('reservation_time', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['restaurant', 'user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Restaurant\Presentation\Resources\BookingResource\Pages\ListBookings::route('/'),
            'view' => \Modules\Restaurant\Presentation\Resources\BookingResource\Pages\ViewBooking::route('/{record}'),
            'edit' => \Modules\Restaurant\Presentation\Resources\BookingResource\Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
