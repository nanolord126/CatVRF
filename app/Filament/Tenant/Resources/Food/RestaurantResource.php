<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food;




use App\Domains\Food\Domain\ValueObjects\RestaurantStatus;
use App\Domains\Food\Infrastructure\Persistence\Eloquent\Models\RestaurantModel;
use App\Filament\Tenant\Resources\Food\RestaurantResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class RestaurantResource extends Resource
{

    protected static ?string $model = RestaurantModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Food';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Restaurant Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options(RestaurantStatus::class)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Location & Contact')
                    ->columns(2)
                    ->schema([
                        Forms\Components\KeyValue::make('address')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->reorderable(),
                        Forms\Components\KeyValue::make('contact')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->reorderable(),
                    ]),
                Forms\Components\Section::make('Menu')
                    ->schema([
                        // This is a placeholder. A real implementation would use a relationship repeater
                        // or a more complex custom component to manage the menu.
                        Forms\Components\Placeholder::make('menu_management')
                            ->label('Menu Sections & Dishes')
                            ->content('Menu management will be handled on a separate, dedicated page or via a relation manager for a better user experience.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('review_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            // Define relation managers here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant()->id);
    }
}
