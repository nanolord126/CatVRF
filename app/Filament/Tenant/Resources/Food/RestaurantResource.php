<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food;

use App\Domains\Food\Models\Restaurant;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

final class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Food';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),

                Forms\Components\TextInput::make('address')
                    ->required(),

                Forms\Components\Select::make('cuisine_type')
                    ->multiple()
                    ->options([
                        'italian' => 'Italian',
                        'japanese' => 'Japanese',
                        'russian' => 'Russian',
                        'asian' => 'Asian',
                        'fast_food' => 'Fast Food',
                    ]),

                Forms\Components\TextInput::make('rating')
                    ->numeric()
                    ->default(0)
                    ->max(5),

                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('schedule_json')
                    ->label('Schedule (JSON)')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('address')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('rating')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Food\RestaurantResource\Pages\ListRestaurants::route('/'),
            'create' => \App\Filament\Tenant\Resources\Food\RestaurantResource\Pages\CreateRestaurant::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Food\RestaurantResource\Pages\ViewRestaurant::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Food\RestaurantResource\Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
