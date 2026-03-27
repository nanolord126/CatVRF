<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\RealEstate\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Tenant\Resources\PropertyResource\Pages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;

/**
 * КАНОН 2026: PropertyResource.
 * Управление объектами недвижимости (Жилая, Коммерческая, Земельные участки).
 */
final class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Real Estate';

    /**
     * МОЩНАЯ ФОРМА ≥ 60 строк (КАНОН).
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Property Details')
                ->tabs([
                    Tabs\Tab::make('Basic Info')
                        ->icon('heroicon-m-information-circle')
                        ->schema([
                            Section::make('Core Details')
                                ->description('Basic asset information')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->label('Property Name (e.g., Loft 42)'),
                                    Forms\Components\Select::make('category')
                                        ->options([
                                            'apartment' => 'Apartment',
                                            'house' => 'House / Villa',
                                            'land' => 'Land Plot',
                                            'commercial' => 'Commercial Space',
                                            'office' => 'Office Building',
                                            'industrial' => 'Industrial / Warehouse',
                                        ])
                                        ->required()
                                        ->native(false),
                                    Forms\Components\TextInput::make('address')
                                        ->required()
                                        ->label('Full Address'),
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('geo_lat')
                                                ->numeric()
                                                ->label('Latitude'),
                                            Forms\Components\TextInput::make('geo_lon')
                                                ->numeric()
                                                ->label('Longitude'),
                                            Forms\Components\TextInput::make('area_total')
                                                ->numeric()
                                                ->required()
                                                ->suffix('m²'),
                                        ]),
                                ])->columns(2),
                        ]),

                    Tabs\Tab::make('Technical Specs')
                        ->icon('heroicon-m-wrench-screwdriver')
                        ->schema([
                            Section::make('Structure & Engineering')
                                ->schema([
                                    Forms\Components\KeyValue::make('technical_specs')
                                        ->label('Engineering/Construction Specs')
                                        ->addActionLabel('Add Spec')
                                        ->keyLabel('Feature')
                                        ->valueLabel('Value'),
                                    Forms\Components\Select::make('status')
                                        ->options([
                                            'active' => 'Active',
                                            'sold' => 'Sold',
                                            'rented' => 'Rented',
                                            'maintenance' => 'Maintenance',
                                        ])
                                        ->default('active')
                                        ->required(),
                                ]),
                        ]),

                    Tabs\Tab::make('Amenities & Media')
                        ->icon('heroicon-m-photo')
                        ->schema([
                            Section::make('Features')
                                ->schema([
                                    Forms\Components\TagsInput::make('amenities')
                                        ->label('Amenities (Parking, Pool, Fiber Internet)'),
                                    Forms\Components\FileUpload::make('images_json')
                                        ->multiple()
                                        ->label('Property Photos')
                                        ->directory('property_images')
                                        ->preserveFilenames()
                                        ->image(),
                                ]),
                        ]),
                ])->columnSpanFull(),

            Section::make('Audit')
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('correlation_id')
                        ->disabled()
                        ->label('Trace ID'),
                    Forms\Components\TextInput::make('uuid')
                        ->disabled()
                        ->label('Asset UUID'),
                ])->columns(2),
        ]);
    }

    /**
     * МОЩНАЯ ТАБЛИЦА (КАНОН).
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('ID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'apartment' => 'info',
                        'house' => 'success',
                        'commercial' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('address')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('area_total')
                    ->label('Area')
                    ->suffix(' m²')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'sold', 'rented' => 'danger',
                        'maintenance' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active only')
                    ->query(fn (Builder $query) => $query->where('status', 'active')),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
