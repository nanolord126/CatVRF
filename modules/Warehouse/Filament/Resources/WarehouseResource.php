<?php

declare(strict_types=1);

namespace Modules\Warehouse\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Warehouse\Infrastructure\Models\WarehouseModel;
use Modules\Warehouse\Domain\Enums\WarehouseTypeEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = WarehouseModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-warehouse';

    protected static ?string $navigationGroup = 'Warehouse';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Warehouse Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Warehouse Name'),
                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->label('Address'),
                        Forms\Components\TextInput::make('branch_id')
                            ->maxLength(100)
                            ->label('Branch ID')
                            ->nullable(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'central' => 'Central',
                                'regional' => 'Regional',
                                'local' => 'Local',
                                'transit' => 'Transit',
                                'returns' => 'Returns',
                            ])
                            ->required()
                            ->label('Type'),
                        Forms\Components\TextInput::make('capacity')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('Capacity (units)'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'central' => 'primary',
                        'regional' => 'success',
                        'local' => 'info',
                        'transit' => 'warning',
                        'returns' => 'danger',
                    })
                    ->label('Type'),
                Tables\Columns\TextColumn::make('address')
                    ->limit(30)
                    ->toggleable()
                    ->label('Address'),
                Tables\Columns\TextColumn::make('capacity')
                    ->numeric()
                    ->sortable()
                    ->label('Capacity'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->numeric()
                    ->sortable()
                    ->label('Current Stock'),
                Tables\Columns\TextColumn::make('utilization_percentage')
                    ->numeric()
                    ->suffix('%')
                    ->color(fn (float $state): string => $state > 80 ? 'danger' : ($state > 50 ? 'warning' : 'success'))
                    ->label('Utilization'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Created At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'central' => 'Central',
                        'regional' => 'Regional',
                        'local' => 'Local',
                        'transit' => 'Transit',
                        'returns' => 'Returns',
                    ])
                    ->label('Type'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Warehouse\Filament\Resources\WarehouseResource\Pages\ListWarehouses::route('/'),
            'create' => \Modules\Warehouse\Filament\Resources\WarehouseResource\Pages\CreateWarehouse::route('/create'),
            'view' => \Modules\Warehouse\Filament\Resources\WarehouseResource\Pages\ViewWarehouse::route('/{record}'),
            'edit' => \Modules\Warehouse\Filament\Resources\WarehouseResource\Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
