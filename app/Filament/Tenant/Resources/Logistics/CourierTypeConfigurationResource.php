<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics;

use App\Domains\Logistics\Models\CourierTypeConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Tenant\Resources\Logistics\CourierTypeConfigurationResource\Pages\CreateCourierTypeConfiguration;
use App\Filament\Tenant\Resources\Logistics\CourierTypeConfigurationResource\Pages\EditCourierTypeConfiguration;
use App\Filament\Tenant\Resources\Logistics\CourierTypeConfigurationResource\Pages\ListCourierTypeConfigurations;

final class CourierTypeConfigurationResource extends Resource
{
    protected static ?string $model = CourierTypeConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Courier Type Rules';

    protected static ?string $navigationGroup = 'Logistics';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Configuration')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'pedestrian' => 'Пеший курьер',
                                'scooter' => 'Самокат',
                                'ebike' => 'Электровелосипед',
                                'car' => 'Автомобиль',
                                'taxi' => 'Такси-курьер',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('city')
                            ->placeholder('Leave empty for default rules'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Capacity Constraints')
                    ->schema([
                        Forms\Components\TextInput::make('max_radius_km')
                            ->numeric()
                            ->step(0.1)
                            ->default(10.0)
                            ->required(),
                        Forms\Components\TextInput::make('max_weight_kg')
                            ->numeric()
                            ->step(0.1)
                            ->default(20.0)
                            ->required(),
                        Forms\Components\TextInput::make('avg_speed_kmh')
                            ->numeric()
                            ->step(0.1)
                            ->default(15.0)
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Time Constraints')
                    ->schema([
                        Forms\Components\TextInput::make('max_delivery_time_min')
                            ->numeric()
                            ->default(60)
                            ->required(),
                        Forms\Components\TextInput::make('parking_time_min')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(100)
                            ->helperText('Lower = higher priority')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Battery Constraints')
                    ->schema([
                        Forms\Components\TextInput::make('battery_threshold')
                            ->numeric()
                            ->placeholder('Minimum battery % for electric vehicles'),
                        Forms\Components\Toggle::make('requires_battery')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Cost & Weather')
                    ->schema([
                        Forms\Components\TextInput::make('cost_multiplier')
                            ->numeric()
                            ->step(0.001)
                            ->default(1.000)
                            ->required(),
                        Forms\Components\KeyValue::make('weather_penalties')
                            ->keyLabel('Condition')
                            ->valueLabel('Penalty')
                            ->default([
                                'rain' => 0.2,
                                'snow' => 0.5,
                                'wind' => 0.1,
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Operating Hours')
                    ->schema([
                        Forms\Components\TimePicker::make('operating_hours.start')
                            ->label('Start Time')
                            ->seconds(false)
                            ->default('08:00'),
                        Forms\Components\TimePicker::make('operating_hours.end')
                            ->label('End Time')
                            ->seconds(false)
                            ->default('22:00'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pedestrian' => 'success',
                        'scooter' => 'info',
                        'ebike' => 'primary',
                        'car' => 'warning',
                        'taxi' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('city')
                    ->placeholder('Default'),
                Tables\Columns\TextColumn::make('max_radius_km')
                    ->numeric(),
                Tables\Columns\TextColumn::make('max_weight_kg')
                    ->numeric(),
                Tables\Columns\TextColumn::make('avg_speed_kmh')
                    ->numeric(),
                Tables\Columns\TextColumn::make('cost_multiplier')
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'pedestrian' => 'Пеший курьер',
                        'scooter' => 'Самокат',
                        'ebike' => 'Электровелосипед',
                        'car' => 'Автомобиль',
                        'taxi' => 'Такси-курьер',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            'index' => ListCourierTypeConfigurations::route('/'),
            'create' => CreateCourierTypeConfiguration::route('/create'),
            'edit' => EditCourierTypeConfiguration::route('/{record}/edit'),
        ];
    }
}
