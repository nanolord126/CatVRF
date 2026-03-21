<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate;

use App\Domains\RealEstate\Models\Property;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

final class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'RealEstate';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('address')
                    ->required(),

                Forms\Components\Select::make('type')
                    ->options([
                        'apartment' => 'Apartment',
                        'house' => 'House',
                        'land' => 'Land',
                        'commercial' => 'Commercial',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('area')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('rooms')
                    ->numeric(),

                Forms\Components\TextInput::make('floor')
                    ->numeric(),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'sold' => 'Sold',
                        'rented' => 'Rented',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('address')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('area')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'apartment' => 'Apartment',
                        'house' => 'House',
                        'land' => 'Land',
                        'commercial' => 'Commercial',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'sold' => 'Sold',
                        'rented' => 'Rented',
                    ]),
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
            'index' => \App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages\ListProperties::route('/'),
            'create' => \App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages\CreateProperty::route('/create'),
            'view' => \App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages\ViewProperty::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages\EditProperty::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
