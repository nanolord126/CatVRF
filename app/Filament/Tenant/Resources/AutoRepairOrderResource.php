<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Auto\Models\AutoRepairOrder;
use App\Domains\Auto\Models\Vehicle;
use App\Domains\Auto\Services\RepairService;
use App\Domains\Auto\Services\AutoAIService;
use App\Filament\Tenant\Resources\AutoRepairOrderResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: AutoRepairOrderResource.
 * Управление СТО (Автосервис) — НЕ ТАКСИ.
 */
final class AutoRepairOrderResource extends Resource
{
    protected static ?string $model = AutoRepairOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    
    protected static ?string $navigationGroup = 'Автосервис (СТО)';

    protected static ?string $label = 'Заказ-наряд';
    
    protected static ?string $pluralLabel = 'Заказ-наряды СТО';

    protected static ?string $slug = 'auto/repair-orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Автомобиль и Клиент')
                    ->schema([
                        Forms\Components\Select::make('vehicle_id')
                            ->relationship('vehicle', 'license_plate')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Автомобиль'),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Клиент (Владелец)'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Принят',
                                'in_progress' => 'В работе',
                                'waiting_parts' => 'Ожидание запчастей',
                                'completed' => 'Готов',
                                'cancelled' => 'Отменен',
                            ])
                            ->default('pending')
                            ->required()
                            ->label('Статус'),
                    ])->columns(3),

                Forms\Components\Section::make('AI Оценка повреждений (Vision)')
                    ->description('Загрузите фото для автоматического анализа повреждений и генерации сметы')
                    ->schema([
                        Forms\Components\FileUpload::make('ai_temp_photo')
                            ->image()
                            ->directory('auto/repair-ai')
                            ->label('Фото повреждений')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                if (!$state) return;
                                
                                Notification::make()
                                    ->title('AI Анализ запущен')
                                    ->body('Анализируем фото для составления сметы...')
                                    ->success()
                                    ->send();
                            }),
                        Forms\Components\JsonEditor::make('ai_estimate')
                            ->label('Результат анализа AI')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Стоимость и Работы')
                    ->schema([
                        Forms\Components\TextInput::make('labor_cost_kopecks')
                            ->numeric()
                            ->label('Стоимость работ (коп)'),
                        Forms\Components\TextInput::make('parts_cost_kopecks')
                            ->numeric()
                            ->label('Стоимость запчастей (коп)')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_cost_kopecks')
                            ->numeric()
                            ->label('Итого (коп)')
                            ->disabled(),
                        Forms\Components\Textarea::make('mechanic_report')
                            ->label('Отчет мастера')
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Список запчастей')
                    ->schema([
                        Forms\Components\Repeater::make('parts_list')
                            ->schema([
                                Forms\Components\TextInput::make('part_id')->label('ID Запчасти'),
                                Forms\Components\TextInput::make('name')->label('Наименование'),
                                Forms\Components\TextInput::make('quantity')->numeric()->label('Кол-во'),
                                Forms\Components\TextInput::make('price')->numeric()->label('Цена за ед.'),
                            ])
                            ->label('Использованные запчасти')
                            ->columnSpanFull(),
                    ]),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListAutoRepairOrder::route('/'),
            'create' => Pages\\CreateAutoRepairOrder::route('/create'),
            'edit' => Pages\\EditAutoRepairOrder::route('/{record}/edit'),
            'view' => Pages\\ViewAutoRepairOrder::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListAutoRepairOrder::route('/'),
            'create' => Pages\\CreateAutoRepairOrder::route('/create'),
            'edit' => Pages\\EditAutoRepairOrder::route('/{record}/edit'),
            'view' => Pages\\ViewAutoRepairOrder::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListAutoRepairOrder::route('/'),
            'create' => Pages\\CreateAutoRepairOrder::route('/create'),
            'edit' => Pages\\EditAutoRepairOrder::route('/{record}/edit'),
            'view' => Pages\\ViewAutoRepairOrder::route('/{record}'),
        ];
    }
}
