<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Auto\Models\Vehicle;
use App\Domains\Auto\Services\AutoService;
use App\Filament\Tenant\Resources\VehicleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: VehicleResource.
 * Управление автопарком в ЛЮТОМ РЕЖИМЕ.
 */
final class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    
    protected static ?string $navigationGroup = 'Автопарк и СТО';

    protected static ?string $slug = 'fleet/vehicles';

    /**
     * Форма создания/редактирования авто.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('brand')
                            ->required()
                            ->maxLength(100)
                            ->label('Марка'),
                        Forms\Components\TextInput::make('model')
                            ->required()
                            ->maxLength(100)
                            ->label('Модель'),
                        Forms\Components\TextInput::make('license_plate')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->label('Госномер'),
                        Forms\Components\TextInput::make('vin')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(17)
                            ->label('VIN-код'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Активен',
                                'busy' => 'В поездке',
                                'repair' => 'В ремонте',
                                'inactive' => 'Неактивен',
                            ])
                            ->default('active')
                            ->required()
                            ->label('Статус'),
                        Forms\Components\Select::make('car_class')
                            ->options([
                                'economy' => 'Эконом',
                                'comfort' => 'Комфорт',
                                'business' => 'Бизнес',
                            ])
                            ->default('economy')
                            ->required()
                            ->label('Класс'),
                    ])->columns(2),

                Forms\Components\Section::make('Технические характеристики')
                    ->schema([
                        Forms\Components\KeyValue::make('technical_specs')
                            ->label('Спецификации (JSON)'),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Теги'),
                    ]),
            ]);
    }

    /**
     * Таблица списка авто.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand')
                    ->searchable()
                    ->sortable()
                    ->label('Марка'),
                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->label('Модель'),
                Tables\Columns\TextColumn::make('license_plate')
                    ->copyable()
                    ->label('Госномер'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'busy',
                        'danger' => 'repair',
                        'secondary' => 'inactive',
                    ])
                    ->label('Статус'),
                Tables\Columns\TextColumn::make('car_class')
                    ->label('Класс'),
                Tables\Columns\TextColumn::make('vin')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('VIN'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Активен',
                        'busy' => 'В поездке',
                        'repair' => 'В ремонте',
                    ]),
                Tables\Filters\SelectFilter::make('car_class')
                    ->options([
                        'economy' => 'Эконом',
                        'comfort' => 'Комфорт',
                        'business' => 'Бизнес',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
