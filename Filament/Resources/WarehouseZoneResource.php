<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseZoneResource\Pages;
use App\Models\WarehouseZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class WarehouseZoneResource extends Resource
{
    protected static ?string $model = WarehouseZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Warehouse';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Zone Information')
                    ->schema([
                        Forms\Components\Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->label('Warehouse'),
                        Forms\Components\TextInput::make('name')
                            ->required()
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
                            ->label('Zone Type'),
                        Forms\Components\TextInput::make('branch_id')
                            ->label('Branch ID'),
                    ]),

                Forms\Components\Section::make('Capacity')
                    ->schema([
                        Forms\Components\TextInput::make('capacity')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->label('Capacity'),
                        Forms\Components\TextInput::make('current_stock')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->label('Current Stock'),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Zone Name'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->searchable()
                    ->label('Warehouse'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'receiving',
                        'success' => 'storage',
                        'warning' => 'picking',
                        'info' => 'packing',
                        'danger' => 'shipping',
                        'gray' => 'quarantine',
                    ])
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
                    ->formatStateUsing(fn ($record) => number_format($record->getUtilizationPercentage(), 1) . '%')
                    ->label('Utilization'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
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
                    ->label('Zone Type'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
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
            'index' => Pages\ListWarehouseZones::route('/'),
            'create' => Pages\CreateWarehouseZone::route('/create'),
            'edit' => Pages\EditWarehouseZone::route('/{record}/edit'),
        ];
    }
}
