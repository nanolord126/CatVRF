<?php

declare(strict_types=1);

namespace Modules\Warehouse\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Warehouse\Infrastructure\Models\WarehouseZoneModel;
use Modules\Warehouse\Domain\Enums\ZoneTypeEnum;

class ZoneResource extends Resource
{
    protected static ?string $model = WarehouseZoneModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Warehouse';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Zone Information')
                    ->schema([
                        Forms\Components\Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Warehouse'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Zone Name'),
                        Forms\Components\Select::make('type')
                            ->options([
                                'receiving' => 'Receiving',
                                'storage' => 'Storage',
                                'picking' => 'Picking',
                                'packing' => 'Packing',
                                'shipping' => 'Shipping',
                                'quarantine' => 'Quarantine',
                                'returns' => 'Returns',
                            ])
                            ->required()
                            ->label('Type'),
                        Forms\Components\TextInput::make('capacity')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('Capacity (units)'),
                        Forms\Components\TextInput::make('branch_id')
                            ->maxLength(100)
                            ->label('Branch ID')
                            ->nullable(),
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
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->searchable()
                    ->sortable()
                    ->label('Warehouse'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'receiving' => 'info',
                        'storage' => 'success',
                        'picking' => 'warning',
                        'packing' => 'primary',
                        'shipping' => 'blue',
                        'quarantine' => 'danger',
                        'returns' => 'gray',
                    })
                    ->label('Type'),
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
                        'receiving' => 'Receiving',
                        'storage' => 'Storage',
                        'picking' => 'Picking',
                        'packing' => 'Packing',
                        'shipping' => 'Shipping',
                        'quarantine' => 'Quarantine',
                        'returns' => 'Returns',
                    ])
                    ->label('Type'),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label('Warehouse'),
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
            'index' => \Modules\Warehouse\Filament\Resources\ZoneResource\Pages\ListZones::route('/'),
            'create' => \Modules\Warehouse\Filament\Resources\ZoneResource\Pages\CreateZone::route('/create'),
            'view' => \Modules\Warehouse\Filament\Resources\ZoneResource\Pages\ViewZone::route('/{record}'),
            'edit' => \Modules\Warehouse\Filament\Resources\ZoneResource\Pages\EditZone::route('/{record}/edit'),
        ];
    }
}
