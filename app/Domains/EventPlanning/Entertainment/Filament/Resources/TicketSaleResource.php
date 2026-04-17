<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class TicketSaleResource extends Resource
{


    protected static ?string $model = TicketSale::class;

        protected static ?string $navigationIcon = 'heroicon-o-ticket';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form
                ->schema([
                    Forms\Components\Select::make('booking_id')
                        ->relationship('booking', 'id')
                        ->required(),
                    Forms\Components\TextInput::make('ticket_number')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('seat_number')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('ticket_price')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('barcode')
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->options(['valid' => 'Valid', 'used' => 'Used', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'])
                        ->required(),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('booking.id'),
                    Tables\Columns\TextColumn::make('seat_number'),
                    Tables\Columns\TextColumn::make('ticket_price')
                        ->money('RUB'),
                    Tables\Columns\TextColumn::make('barcode'),
                    Tables\Columns\TextColumn::make('status')->badge(),
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
                'index' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\TicketSaleResource\Pages\ListTicketSales::class,
                'create' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\TicketSaleResource\Pages\CreateTicketSale::class,
                'edit' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\TicketSaleResource\Pages\EditTicketSale::class,
            ];
        }
}
