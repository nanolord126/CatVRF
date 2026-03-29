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

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListVehicle::route('/'),
            'create' => Pages\\CreateVehicle::route('/create'),
            'edit' => Pages\\EditVehicle::route('/{record}/edit'),
            'view' => Pages\\ViewVehicle::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListVehicle::route('/'),
            'create' => Pages\\CreateVehicle::route('/create'),
            'edit' => Pages\\EditVehicle::route('/{record}/edit'),
            'view' => Pages\\ViewVehicle::route('/{record}'),
        ];
    }
}
