<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Education\Kids\Models\KidsProduct;
use App\Domains\Education\Kids\Models\KidsStore;
use App\Filament\Resources\TenantResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * KidsProductResource - Admin UI for Children Goods.
 * Requirement: Form >= 60 lines.
 * Layer: Filament Resources (5/9)
 */
final class KidsProductResource extends Resource
{
    protected static ?string $model = KidsProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Baby & Kids';
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Core Information')
                            ->description('Basic product identity.')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Wooden Block Set'),
                                Forms\Components\RichEditor::make('description')
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('store_id')
                                    ->label('Store / Warehouse')
                                    ->required()
                                    ->options(fn() => KidsStore::pluck('name', 'id'))
                                    ->searchable(),
                            ])->columns(2),

                        Forms\Components\Section::make('Finance & Stock')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Price (Kopecks)')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('RUB kop'),
                                Forms\Components\TextInput::make('stock_quantity')
                                    ->label('Inventory Count')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('sku')
                                    ->label('Stock Keeping Unit (SKU)')
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('barcode')
                                    ->label('UPC / EAN Barcode')
                                    ->maxLength(50),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Safety & Quality')
                            ->description('Mandatory children safety attributes.')
                            ->schema([
                                Forms\Components\Select::make('safety_class')
                                    ->label('Safety Level')
                                    ->required()
                                    ->options([
                                        'A' => 'Premium Safety (0+ months)',
                                        'B' => 'Standard Safety (3+ years)',
                                        'C' => 'Advanced Safety (8+ years)',
                                    ])
                                    ->default('B'),
                                Forms\Components\Fieldset::make('Age Range (Months)')
                                    ->schema([
                                        Forms\Components\TextInput::make('age_range.min_months')
                                            ->label('Min Months')
                                            ->numeric()
                                            ->default(0),
                                        Forms\Components\TextInput::make('age_range.max_months')
                                            ->label('Max Months')
                                            ->numeric()
                                            ->default(120),
                                    ])->columns(2),
                                Forms\Components\KeyValue::make('material_details')
                                    ->label('Material Composition')
                                    ->keyLabel('Material')
                                    ->valueLabel('Percentage (%)')
                                    ->default(['Wood' => '100']),
                                Forms\Components\TextInput::make('origin_country')
                                    ->label('Country of Origin')
                                    ->required()
                                    ->default('Russia'),
                            ]),
                        
                        Forms\Components\Section::make('Identity & Tracking')
                            ->schema([
                                Forms\Components\TextInput::make('uuid')
                                    ->label('UUID')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated'),
                                Forms\Components\TextInput::make('correlation_id')
                                    ->label('Correlation Trace ID')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TagsInput::make('tags')
                                    ->label('Analytics Tags')
                                    ->placeholder('e.g. educational, eco-friendly'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('rub', 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state): string => $state < 5 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('safety_class')
                    ->label('Safety')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'warning',
                        'C' => 'info',
                    }),
                Tables\Columns\TextColumn::make('age_range_label')
                    ->label('Target Age')
                    ->getStateUsing(fn($record) => floor($record->age_range['min_months'] / 12) . '-' . floor($record->age_range['max_months'] / 12) . ' yr'),
                Tables\Columns\TextColumn::make('sku')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('safety_class')
                    ->options([
                        'A' => 'Class A (0+)',
                        'B' => 'Class B (3+)',
                        'C' => 'Class C (8+)',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Toy, Clothing metadata relations
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKidsProducts::route('/'),
            'create' => Pages\CreateKidsProduct::route('/create'),
            'edit' => Pages\EditKidsProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
