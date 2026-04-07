<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Filament\Resources\BookingResource;

use App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Models\BookingModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class BookingResource extends Resource
{
    protected static ?string $model = BookingModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Hotels';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('hotel_id')
                    ->relationship('hotel', 'name')
                    ->required(),
                Forms\Components\Select::make('room_id')
                    ->relationship('room', 'type')
                    ->required(),
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('check_in_date')
                    ->required(),
                Forms\Components\DateTimePicker::make('check_out_date')
                    ->required(),
                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hotel.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('room.type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('rub')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Hotels\Presentation\Filament\Resources\BookingResource\Pages\ListBookings::route('/'),
            'create' => \App\Domains\Hotels\Presentation\Filament\Resources\BookingResource\Pages\CreateBooking::route('/create'),
            'edit' => \App\Domains\Hotels\Presentation\Filament\Resources\BookingResource\Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
