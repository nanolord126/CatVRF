<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class TicketResource extends Resource
{

    protected static ?string $model = Ticket::class;
        protected static ?string $navigationIcon = 'heroicon-o-ticket';
        protected static ?string $navigationLabel = 'Билеты';
        protected static ?int $navigationSort = 3;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Информация')
                        ->schema([
                            Forms\Components\Select::make('event_id')
                                ->label('Событие')
                                ->relationship('event', 'title')
                                ->required(),
                            Forms\Components\Select::make('ticket_type_id')
                                ->label('Тип билета')
                                ->relationship('ticketType', 'name')
                                ->required(),
                            Forms\Components\Select::make('buyer_id')
                                ->label('Покупатель')
                                ->relationship('buyer', 'email')
                                ->required(),
                            Forms\Components\TextInput::make('ticket_number')
                                ->label('Номер билета')
                                ->disabled(),
                            Forms\Components\TextInput::make('qr_code')
                                ->label('QR код')
                                ->disabled(),
                            Forms\Components\Select::make('status')
                                ->label('Статус')
                                ->options([
                                    'available' => 'Доступен',
                                    'reserved' => 'Зарезервирован',
                                    'sold' => 'Продан',
                                    'scanned' => 'Отсканирован',
                                    'cancelled' => 'Отменен',
                                ])
                                ->disabled(),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('ticket_number')
                        ->label('Номер')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('event.title')
                        ->label('Событие')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn($state) => match($state) {
                            'reserved' => 'warning',
                            'sold' => 'success',
                            'scanned' => 'info',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }),
                    Tables\Columns\TextColumn::make('buyer.email')
                        ->label('Покупатель')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('sold_at')
                        ->label('Продан')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'available' => 'Доступен',
                            'reserved' => 'Зарезервирован',
                            'sold' => 'Продан',
                            'scanned' => 'Отсканирован',
                            'cancelled' => 'Отменен',
                        ]),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListTickets::route('/'),
                'view' => Pages\ViewTicket::route('/{record}'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()?->id)
                ->with(['event', 'ticketType', 'buyer']);
        }
}
