<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources;

use Modules\Flowers\Infrastructure\Models\FlowerModel;
use Modules\Flowers\Domain\Enums\FreshnessStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class FlowerResource extends Resource
{
    protected static ?string $model = FlowerModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Flowers CRM';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Flower Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->options([
                                'rose' => 'Rose',
                                'tulip' => 'Tulip',
                                'lily' => 'Lily',
                                'chrysanthemum' => 'Chrysanthemum',
                                'peony' => 'Peony',
                                'orchid' => 'Orchid',
                                'carnation' => 'Carnation',
                                'daisy' => 'Daisy',
                                'sunflower' => 'Sunflower',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('color')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('variety')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Supplier Information')
                    ->schema([
                        Forms\Components\TextInput::make('supplier')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('supplier_code')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Stock & Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('stock_quantity')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('reserved_quantity')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('minimum_stock')
                            ->required()
                            ->numeric()
                            ->default(10),
                        Forms\Components\TextInput::make('cost_price')
                            ->required()
                            ->numeric()
                            ->prefix('₽'),
                        Forms\Components\TextInput::make('selling_price')
                            ->required()
                            ->numeric()
                            ->prefix('₽'),
                        Forms\Components\Select::make('unit')
                            ->options([
                                'stem' => 'Stem',
                                'bunch' => 'Bunch',
                                'box' => 'Box',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('units_per_bunch')
                            ->numeric()
                            ->default(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Freshness & Expiry')
                    ->schema([
                        Forms\Components\DatePicker::make('expiry_date')
                            ->required(),
                        Forms\Components\DatePicker::make('received_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('freshness_status')
                            ->options([
                                'fresh' => 'Fresh',
                                'good' => 'Good',
                                'aging' => 'Aging',
                                'expiring_soon' => 'Expiring Soon',
                                'expired' => 'Expired',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('stem_length_cm')
                            ->numeric()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('storage_conditions')
                            ->rows(2),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('color')
                    ->searchable(),
                BadgeColumn::make('freshness_status')
                    ->colors([
                        'success' => 'fresh',
                        'info' => 'good',
                        'warning' => 'aging',
                        'danger' => 'expiring_soon',
                        'gray' => 'expired',
                    ]),
                TextColumn::make('stock_quantity')
                    ->sortable(),
                TextColumn::make('reserved_quantity')
                    ->sortable(),
                TextColumn::make('available_stock')
                    ->label('Available')
                    ->getStateUsing(fn ($record) => $record->stock_quantity - $record->reserved_quantity)
                    ->sortable(),
                TextColumn::make('expiry_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->money('RUB')
                    ->sortable(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'rose' => 'Rose',
                        'tulip' => 'Tulip',
                        'lily' => 'Lily',
                        'chrysanthemum' => 'Chrysanthemum',
                        'peony' => 'Peony',
                        'orchid' => 'Orchid',
                        'carnation' => 'Carnation',
                        'daisy' => 'Daisy',
                        'sunflower' => 'Sunflower',
                        'other' => 'Other',
                    ]),
                SelectFilter::make('freshness_status')
                    ->options([
                        'fresh' => 'Fresh',
                        'good' => 'Good',
                        'aging' => 'Aging',
                        'expiring_soon' => 'Expiring Soon',
                        'expired' => 'Expired',
                    ]),
                SelectFilter::make('color')
                    ->options([
                        'red' => 'Red',
                        'white' => 'White',
                        'pink' => 'Pink',
                        'yellow' => 'Yellow',
                        'orange' => 'Orange',
                        'purple' => 'Purple',
                        'blue' => 'Blue',
                        'mixed' => 'Mixed',
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

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Flowers\Filament\Resources\FlowerResource\Pages\ListFlowers::route('/'),
            'create' => \Modules\Flowers\Filament\Resources\FlowerResource\Pages\CreateFlower::route('/create'),
            'view' => \Modules\Flowers\Filament\Resources\FlowerResource\Pages\ViewFlower::route('/{record}'),
            'edit' => \Modules\Flowers\Filament\Resources\FlowerResource\Pages\EditFlower::route('/{record}/edit'),
        ];
    }
}
