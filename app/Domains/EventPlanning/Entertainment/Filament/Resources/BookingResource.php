<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Filament\Resources;

use App\Domains\EventPlanning\Entertainment\Models\Booking;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('venue_id')
                    ->relationship('venue', 'name')
                    ->required(),
                Forms\Components\Select::make('event_schedule_id')
                    ->relationship('eventSchedule', 'id')
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                Forms\Components\TextInput::make('number_of_seats')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('total_price')
                    ->numeric()
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->options(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'completed' => 'Completed'])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.email'),
                Tables\Columns\TextColumn::make('eventSchedule.start_time'),
                Tables\Columns\TextColumn::make('number_of_seats'),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('RUB'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['pending' => 'warning', 'confirmed' => 'info', 'completed' => 'success', 'cancelled' => 'danger']),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\BookingResource\Pages\ListBookings::class,
            'create' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\BookingResource\Pages\CreateBooking::class,
            'edit' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\BookingResource\Pages\EditBooking::class,
        ];
    }
}
