<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources;

use App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models\EloquentAdInventory;
use App\Domains\Advertising\Presentation\Filament\Resources\AdInventoryResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AdInventoryResource extends Resource
{
    protected static ?string $model = EloquentAdInventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Advertising';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('publisher_id')
                    ->relationship('publisher', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Publisher'),

                Forms\Components\Select::make('inventory_type')
                    ->options([
                        'short' => 'Short Video',
                        'banner' => 'Banner',
                        'video' => 'Video',
                        'native' => 'Native',
                    ])
                    ->default('banner')
                    ->required()
                    ->label('Inventory Type'),

                Forms\Components\Select::make('placement')
                    ->options([
                        'feed' => 'Feed',
                        'story' => 'Story',
                        'search' => 'Search',
                        'interstitial' => 'Interstitial',
                    ])
                    ->default('feed')
                    ->required()
                    ->label('Placement'),

                Forms\Components\TextInput::make('available_impressions')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->label('Available Impressions'),

                Forms\Components\TextInput::make('reserved_impressions')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->label('Reserved Impressions'),

                Forms\Components\DateTimePicker::make('available_from')
                    ->required()
                    ->label('Available From'),

                Forms\Components\DateTimePicker::make('available_until')
                    ->required()
                    ->label('Available Until'),

                Forms\Components\Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'reserved' => 'Reserved',
                        'sold_out' => 'Sold Out',
                    ])
                    ->default('available')
                    ->required()
                    ->label('Status'),

                Forms\Components\Textarea::make('targeting_restrictions')
                    ->rows(3)
                    ->label('Targeting Restrictions (JSON)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('publisher.name')
                    ->searchable()
                    ->sortable()
                    ->label('Publisher'),

                Tables\Columns\TextColumn::make('inventory_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'short' => 'primary',
                        'banner' => 'secondary',
                        'video' => 'info',
                        'native' => 'success',
                    })
                    ->label('Type'),

                Tables\Columns\TextColumn::make('placement')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'feed' => 'primary',
                        'story' => 'warning',
                        'search' => 'info',
                        'interstitial' => 'danger',
                    })
                    ->label('Placement'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'sold_out' => 'danger',
                    })
                    ->label('Status'),

                Tables\Columns\TextColumn::make('available_impressions')
                    ->numeric()
                    ->sortable()
                    ->label('Available'),

                Tables\Columns\TextColumn::make('reserved_impressions')
                    ->numeric()
                    ->sortable()
                    ->label('Reserved'),

                Tables\Columns\TextColumn::make('remaining_impressions')
                    ->numeric()
                    ->label('Remaining')
                    ->formatStateUsing(fn ($record): int => $record->available_impressions - $record->reserved_impressions),

                Tables\Columns\TextColumn::make('available_from')
                    ->dateTime()
                    ->sortable()
                    ->label('Available From'),

                Tables\Columns\TextColumn::make('available_until')
                    ->dateTime()
                    ->sortable()
                    ->label('Available Until'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'reserved' => 'Reserved',
                        'sold_out' => 'Sold Out',
                    ]),

                Tables\Filters\SelectFilter::make('inventory_type')
                    ->options([
                        'short' => 'Short Video',
                        'banner' => 'Banner',
                        'video' => 'Video',
                        'native' => 'Native',
                    ]),

                Tables\Filters\SelectFilter::make('placement')
                    ->options([
                        'feed' => 'Feed',
                        'story' => 'Story',
                        'search' => 'Search',
                        'interstitial' => 'Interstitial',
                    ]),

                Tables\Filters\Filter::make('available')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'available'))
                    ->label('Available Inventory'),
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
            'index' => Pages\ListAdInventory::route('/'),
            'create' => Pages\CreateAdInventory::route('/create'),
            'edit' => Pages\EditAdInventory::route('/{record}/edit'),
        ];
    }
}
