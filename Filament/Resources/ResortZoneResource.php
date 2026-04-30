<?php

declare(strict_types=1);

namespace Filament\Resources;

use App\Domains\Logistics\Models\ResortZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class ResortZoneResource extends Resource
{
    protected static ?string $model = ResortZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Resort Zones';

    protected static ?string $navigationGroup = 'Logistics';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Zone Information')
                    ->description('Define resort/spit/beach zones for adaptive logistics optimization')
                    ->schema([
                        Forms\Components\TextInput::make('zone_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Zone Name')
                            ->placeholder('e.g., Anapa Spit'),

                        Forms\Components\Select::make('zone_type')
                            ->required()
                            ->options([
                                'beach' => 'Beach',
                                'spit' => 'Spit',
                                'resort_base' => 'Resort Base',
                                'coastal_road' => 'Coastal Road',
                            ])
                            ->label('Zone Type')
                            ->default('beach'),

                        Forms\Components\Textarea::make('polygon_geojson')
                            ->required()
                            ->label('Polygon GeoJSON')
                            ->placeholder('{"type":"Polygon","coordinates":[[[45.0,37.2],[45.0,37.4],[45.1,37.4],[45.1,37.2],[45.0,37.2]]]}')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Optimization Settings')
                    ->description('Adaptive batching and routing parameters')
                    ->schema([
                        Forms\Components\Toggle::make('is_resort_spit')
                            ->label('Is Resort/Spit Zone')
                            ->helperText('Enables 1.5km max batch distance and 12% deadhead threshold')
                            ->default(false),

                        Forms\Components\TextInput::make('max_batch_distance_km')
                            ->numeric()
                            ->minValue(0.1)
                            ->maxValue(10.0)
                            ->step(0.1)
                            ->label('Max Batch Distance (km)')
                            ->default(0.8)
                            ->helperText('Urban: 0.8km, Resort: 1.5km'),

                        Forms\Components\TextInput::make('deadhead_ratio_threshold')
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(1.0)
                            ->step(0.01)
                            ->label('Deadhead Ratio Threshold')
                            ->default(0.08)
                            ->helperText('Urban: 0.08, Resort: 0.12'),

                        Forms\Components\TextInput::make('linear_density_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.1)
                            ->label('Linear Density Score')
                            ->default(0.5)
                            ->helperText('Higher = more linear/spit-like (0-1)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Seasonal Settings')
                    ->description('Seasonal availability and peak hours')
                    ->schema([
                        Forms\Components\Toggle::make('is_seasonal')
                            ->label('Is Seasonal')
                            ->helperText('Zone is only active during specific months')
                            ->default(false),

                        Forms\Components\Select::make('season_start_month')
                            ->options([
                                1 => 'January', 2 => 'February', 3 => 'March',
                                4 => 'April', 5 => 'May', 6 => 'June',
                                7 => 'July', 8 => 'August', 9 => 'September',
                                10 => 'October', 11 => 'November', 12 => 'December',
                            ])
                            ->label('Season Start Month')
                            ->default(5),

                        Forms\Components\Select::make('season_end_month')
                            ->options([
                                1 => 'January', 2 => 'February', 3 => 'March',
                                4 => 'April', 5 => 'May', 6 => 'June',
                                7 => 'July', 8 => 'August', 9 => 'September',
                                10 => 'October', 11 => 'November', 12 => 'December',
                            ])
                            ->label('Season End Month')
                            ->default(9),

                        Forms\Components\TagsInput::make('peak_hours')
                            ->label('Peak Hours')
                            ->placeholder('e.g., 10, 11, 12, 13, 14, 15, 16')
                            ->helperText('Hours when demand peaks (0-23)')
                            ->suggestions(['9', '10', '11', '12', '13', '14', '15', '16', '17', '18']),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Courier Preferences')
                    ->description('Courier type preferences and heat limits')
                    ->schema([
                        Forms\Components\TagsInput::make('preferred_types')
                            ->label('Preferred Courier Types')
                            ->placeholder('e.g., pedestrian, scooter, car')
                            ->suggestions(['pedestrian', 'scooter', 'ebike', 'car', 'taxi']),

                        Forms\Components\TextInput::make('pedestrian_max_radius_km')
                            ->numeric()
                            ->minValue(0.1)
                            ->maxValue(5.0)
                            ->step(0.1)
                            ->label('Pedestrian Max Radius (km)')
                            ->default(0.8)
                            ->helperText('Maximum distance for pedestrian couriers in this zone'),

                        Forms\Components\TextInput::make('pedestrian_heat_limit_celsius')
                            ->numeric()
                            ->minValue(20)
                            ->maxValue(50)
                            ->step(1)
                            ->label('Pedestrian Heat Limit (°C)')
                            ->default(35.0)
                            ->helperText('Above this temperature, pedestrian couriers are restricted'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statistics')
                    ->description('Order density and performance metrics')
                    ->schema([
                        Forms\Components\TextInput::make('avg_orders_per_hour')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->label('Avg Orders per Hour')
                            ->default(0)
                            ->readOnly(),

                        Forms\Components\TextInput::make('avg_orders_per_km2')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->label('Avg Orders per km²')
                            ->default(0)
                            ->readOnly(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('zone_name')
                    ->label('Zone Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('zone_type')
                    ->label('Zone Type')
                    ->colors([
                        'danger' => 'spit',
                        'warning' => 'beach',
                        'success' => 'resort_base',
                        'info' => 'coastal_road',
                    ]),

                Tables\Columns\IconColumn::make('is_resort_spit')
                    ->label('Resort/Spit')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('max_batch_distance_km')
                    ->label('Max Batch (km)')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 1).' km'),

                Tables\Columns\TextColumn::make('deadhead_ratio_threshold')
                    ->label('Deadhead Threshold')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ($state * 100).'%'),

                Tables\Columns\IconColumn::make('is_seasonal')
                    ->label('Seasonal')
                    ->boolean()
                    ->trueIcon('heroicon-o-calendar')
                    ->falseIcon('heroicon-o-calendar-days'),

                Tables\Columns\TextColumn::make('season_start_month')
                    ->label('Season')
                    ->formatStateUsing(fn ($record) => $record->is_seasonal
                        ? $record->season_start_month.'-'.$record->season_end_month
                        : 'N/A'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('zone_type')
                    ->options([
                        'beach' => 'Beach',
                        'spit' => 'Spit',
                        'resort_base' => 'Resort Base',
                        'coastal_road' => 'Coastal Road',
                    ]),

                Tables\Filters\TernaryFilter::make('is_resort_spit')
                    ->label('Resort/Spit Zone')
                    ->placeholder('All')
                    ->trueLabel('Yes')
                    ->falseLabel('No'),

                Tables\Filters\TernaryFilter::make('is_seasonal')
                    ->label('Seasonal')
                    ->placeholder('All')
                    ->trueLabel('Yes')
                    ->falseLabel('No'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('zone_name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResortZones::route('/'),
            'create' => Pages\CreateResortZone::route('/create'),
            'edit' => Pages\EditResortZone::route('/{record}/edit'),
        ];
    }
}
