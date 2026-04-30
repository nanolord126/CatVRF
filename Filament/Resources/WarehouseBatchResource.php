<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseBatchResource\Pages;
use App\Models\WarehouseBatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class WarehouseBatchResource extends Resource
{
    protected static ?string $model = WarehouseBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Warehouse';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Batch Information')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->label('Product'),
                        Forms\Components\TextInput::make('batch_number')
                            ->required()
                            ->label('Batch Number'),
                        Forms\Components\TextInput::make('lot_number')
                            ->required()
                            ->label('Lot Number'),
                        Forms\Components\DatePicker::make('manufacture_date')
                            ->required()
                            ->label('Manufacture Date'),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->required()
                            ->label('Expiry Date'),
                    ]),

                Forms\Components\Section::make('Stock Information')
                    ->schema([
                        Forms\Components\TextInput::make('initial_quantity')
                            ->numeric()
                            ->required()
                            ->label('Initial Quantity'),
                        Forms\Components\TextInput::make('current_quantity')
                            ->numeric()
                            ->required()
                            ->label('Current Quantity'),
                        Forms\Components\TextInput::make('purchase_price')
                            ->numeric()
                            ->step(0.01)
                            ->label('Purchase Price'),
                    ]),

                Forms\Components\Section::make('Location')
                    ->schema([
                        Forms\Components\Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->label('Warehouse'),
                        Forms\Components\Select::make('zone_id')
                            ->relationship('zone', 'name')
                            ->label('Zone'),
                        Forms\Components\Select::make('bin_id')
                            ->relationship('bin', 'name')
                            ->label('Bin'),
                    ]),

                Forms\Components\Section::make('Supplier Information')
                    ->schema([
                        Forms\Components\TextInput::make('supplier_id')
                            ->label('Supplier ID'),
                        Forms\Components\TextInput::make('supplier_name')
                            ->label('Supplier Name'),
                        Forms\Components\TextInput::make('certificate_number')
                            ->label('Certificate Number'),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'expiring_soon' => 'Expiring Soon',
                                'expired' => 'Expired',
                                'depleted' => 'Depleted',
                                'quarantine' => 'Quarantine',
                            ])
                            ->default('active')
                            ->required()
                            ->label('Status'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch_number')
                    ->searchable()
                    ->sortable()
                    ->label('Batch Number'),
                Tables\Columns\TextColumn::make('product.name')
                    ->searchable()
                    ->label('Product'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->label('Expiry Date'),
                Tables\Columns\TextColumn::make('current_quantity')
                    ->numeric()
                    ->sortable()
                    ->label('Current Qty'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'expired',
                        'warning' => 'expiring_soon',
                        'success' => 'active',
                        'secondary' => 'depleted',
                        'gray' => 'quarantine',
                    ])
                    ->label('Status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expiring_soon' => 'Expiring Soon',
                        'expired' => 'Expired',
                        'depleted' => 'Depleted',
                        'quarantine' => 'Quarantine',
                    ])
                    ->label('Status'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn ($query) => $query->expiringSoon(30))
                    ->label('Expiring Soon (30 days)'),
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
            'index' => Pages\ListWarehouseBatches::route('/'),
            'create' => Pages\CreateWarehouseBatch::route('/create'),
            'edit' => Pages\EditWarehouseBatch::route('/{record}/edit'),
        ];
    }
}
