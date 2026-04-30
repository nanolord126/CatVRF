<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Enums\IoTProtocol;
use Modules\Restaurant\Infrastructure\Models\IoTDeviceModel;

final class IoTDeviceResource extends Resource
{
    protected static ?string $model = IoTDeviceModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Device Information')
                    ->schema([
                        Forms\Components\TextInput::make('device_identifier')
                            ->label('Device Identifier')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('MAC address, serial number, or unique ID'),

                        Forms\Components\TextInput::make('name')
                            ->label('Device Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Device Type')
                            ->options(IoTDeviceType::class)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('protocol')
                            ->label('Protocol')
                            ->options(IoTProtocol::class)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('kitchen_station_id')
                            ->label('Kitchen Station')
                            ->relationship('kitchenStation', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Connection Settings')
                    ->schema([
                        Forms\Components\Textarea::make('connection_config')
                            ->label('Connection Config (JSON)')
                            ->rows(3)
                            ->helperText('JSON configuration for connection parameters')
                            ->json(),

                        Forms\Components\TextInput::make('broker_url')
                            ->label('Broker URL')
                            ->url()
                            ->helperText('MQTT broker URL (e.g., mqtt://localhost:1883)')
                            ->nullable(),

                        Forms\Components\TextInput::make('topic_prefix')
                            ->label('Topic Prefix')
                            ->helperText('MQTT topic prefix (e.g., iot/device)')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('metadata')
                            ->label('Metadata (JSON)')
                            ->rows(3)
                            ->json(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->nullable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('device_identifier')
                    ->label('Identifier')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (IoTDeviceType $state) => $state->label())
                    ->colors([
                        'danger' => IoTDeviceType::TEMPERATURE_SENSOR,
                        'warning' => IoTDeviceType::WEIGHT_SCALE,
                        'success' => IoTDeviceType::KDS_DISPLAY,
                        'info' => IoTDeviceType::GENERIC,
                    ]),

                Tables\Columns\BadgeColumn::make('protocol')
                    ->label('Protocol')
                    ->formatStateUsing(fn (IoTProtocol $state) => $state->label()),

                Tables\Columns\IconColumn::make('is_online')
                    ->label('Online')
                    ->boolean()
                    ->trueIcon('heroicon-o-signal')
                    ->falseIcon('heroicon-o-signal-slash')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('kitchenStation.name')
                    ->label('Station')
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Device Type')
                    ->options(IoTDeviceType::class),

                Tables\Filters\SelectFilter::make('protocol')
                    ->label('Protocol')
                    ->options(IoTProtocol::class),

                Tables\Filters\TernaryFilter::make('is_online')
                    ->label('Online Status')
                    ->placeholder('All devices')
                    ->trueLabel('Online only')
                    ->falseLabel('Offline only')
                    ->queries(
                        true: fn ($query) => $query->where('is_online', true),
                        false: fn ($query) => $query->where('is_online', false),
                    ),

                Tables\Filters\SelectFilter::make('kitchen_station_id')
                    ->label('Kitchen Station')
                    ->relationship('kitchenStation', 'name'),
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
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            'telemetry' => Tables\Columns\TextColumn::make('telemetry'),
            'alertRules' => Tables\Columns\TextColumn::make('alertRules'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Restaurant\Presentation\Resources\IoTDeviceResource\Pages\ListIoTDevices::route('/'),
            'create' => \Modules\Restaurant\Presentation\Resources\IoTDeviceResource\Pages\CreateIoTDevice::route('/create'),
            'view' => \Modules\Restaurant\Presentation\Resources\IoTDeviceResource\Pages\ViewIoTDevice::route('/{record}'),
            'edit' => \Modules\Restaurant\Presentation\Resources\IoTDeviceResource\Pages\EditIoTDevice::route('/{record}/edit'),
        ];
    }
}
