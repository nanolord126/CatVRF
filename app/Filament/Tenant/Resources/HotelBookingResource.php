<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\HotelBookingResource\Pages;
use Modules\Hotels\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HotelBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Hotel Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Booking Details')
                    ->schema([
                        Components\Select::make('room_id')
                            ->relationship('room', 'number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Components\DateTimePicker::make('check_in')
                            ->required(),
                        Components\DateTimePicker::make('check_out')
                            ->required(),
                        Components\TextInput::make('total_price')
                            ->numeric()
                            ->prefix('₽')
                            ->live()
                            ->required(),
                        Components\Placeholder::make('commission_info')
                            ->label('Estimated Platform Commission')
                            ->content(function (Forms\Get $get) {
                                if (!$get('total_price')) return '₽ 0.00';
                                $percent = (tenant('commission_uplift') || !tenant('inn')) ? 0.30 : 0.10;
                                $amount = round($get('total_price') * $percent, 2);
                                return '₽ ' . number_format($amount, 2) . ' (' . ($percent * 100) . '%)';
                            }),
                        Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('room.number')
                    ->label('Room')
                    ->sortable()
                    ->searchable(),
                Columns\TextColumn::make('check_in')
                    ->dateTime()
                    ->sortable(),
                Columns\TextColumn::make('check_out')
                    ->dateTime()
                    ->sortable(),
                Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'cancelled',
                    ]),
                Columns\TextColumn::make('total_price')
                    ->money('RUB')
                    ->sortable(),
                Columns\TextColumn::make('commission')
                    ->label('Fee')
                    ->getStateUsing(fn (Booking $record) => $record->calculateCommission())
                    ->money('RUB'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('delete_bookings')),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_bookings');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_bookings');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit_bookings');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_bookings');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHotelBookings::route('/'),
        ];
    }
}
