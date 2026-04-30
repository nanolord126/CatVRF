<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources;

use App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models\EloquentAuction;
use App\Domains\Advertising\Presentation\Filament\Resources\AuctionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AuctionResource extends Resource
{
    protected static ?string $model = EloquentAuction::class;

    protected static ?string $navigationIcon = 'heroicon-o-gavel';

    protected static ?string $navigationGroup = 'Advertising';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Auction Name'),

                Forms\Components\Select::make('type')
                    ->options([
                        'forward' => 'Forward (English)',
                        'dutch' => 'Dutch',
                        'sealed_bid' => 'Sealed Bid (VCG)',
                    ])
                    ->default('forward')
                    ->required()
                    ->label('Auction Type'),

                Forms\Components\Select::make('status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'active' => 'Active',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('upcoming')
                    ->required()
                    ->label('Status'),

                Forms\Components\DateTimePicker::make('start_at')
                    ->required()
                    ->label('Start At'),

                Forms\Components\DateTimePicker::make('end_at')
                    ->required()
                    ->label('End At'),

                Forms\Components\TextInput::make('starting_price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->label('Starting Price (kopeks)'),

                Forms\Components\TextInput::make('current_price')
                    ->numeric()
                    ->default(0)
                    ->label('Current Price (kopeks)')
                    ->disabled(),

                Forms\Components\TextInput::make('reserve_price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->label('Reserve Price (kopeks)'),

                Forms\Components\Select::make('inventory_id')
                    ->relationship('inventory', 'id')
                    ->searchable()
                    ->preload()
                    ->label('Inventory'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name')
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'forward' => 'primary',
                        'dutch' => 'warning',
                        'sealed_bid' => 'info',
                    })
                    ->label('Type'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'upcoming' => 'gray',
                        'active' => 'success',
                        'closed' => 'info',
                        'cancelled' => 'danger',
                    })
                    ->label('Status'),

                Tables\Columns\TextColumn::make('starting_price')
                    ->money('RUB', divideBy: 100)
                    ->sortable()
                    ->label('Starting Price'),

                Tables\Columns\TextColumn::make('current_price')
                    ->money('RUB', divideBy: 100)
                    ->sortable()
                    ->label('Current Price'),

                Tables\Columns\TextColumn::make('reserve_price')
                    ->money('RUB', divideBy: 100)
                    ->sortable()
                    ->label('Reserve Price'),

                Tables\Columns\TextColumn::make('start_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Start At'),

                Tables\Columns\TextColumn::make('end_at')
                    ->dateTime()
                    ->sortable()
                    ->label('End At'),

                Tables\Columns\TextColumn::make('bid_history')
                    ->badge()
                    ->label('Bids')
                    ->formatStateUsing(fn (array $state): int => count($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'active' => 'Active',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'forward' => 'Forward',
                        'dutch' => 'Dutch',
                        'sealed_bid' => 'Sealed Bid',
                    ]),

                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'active'))
                    ->label('Active Auctions'),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAuctions::route('/'),
            'create' => Pages\CreateAuction::route('/create'),
            'edit' => Pages\EditAuction::route('/{record}/edit'),
        ];
    }
}
