<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto;

use App\Domains\Auto\Models\AutoRepairOrder;
use App\Domains\Auto\Models\AutoVehicle;
use App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * AutoRepairOrderResource — Канон 2026.
 * Управление заказ-нарядами СТО.
 */
final class AutoRepairOrderResource extends Resource
{
    protected static ?string $model = AutoRepairOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Автосервис';

    protected static ?string $label = 'Заказ-наряд';

    protected static ?string $pluralLabel = 'Заказ-наряды';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Автомобиль и Клиент')
                    ->schema([
                        Forms\Components\Select::make('auto_vehicle_id')
                            ->label('Автомобиль')
                            ->relationship('vehicle', 'vin')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (AutoVehicle $record) => "{$record->brand} {$record->model} ({$record->vin})"),
                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидание',
                                'in_progress' => 'В работе',
                                'completed' => 'Завершен',
                                'cancelled' => 'Отменен',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\DateTimePicker::make('planned_at')
                            ->label('Плановая дата')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Жалобы и Работы')
                    ->schema([
                        Forms\Components\Textarea::make('client_complaint')
                            ->label('Жалоба клиента')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('mechanic_report')
                            ->label('Отчет мастера')
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Смета')
                    ->schema([
                        Forms\Components\TextInput::make('labor_cost_kopecks')
                            ->label('Стоимость работ (коп)')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('parts_cost_kopecks')
                            ->label('Стоимость запчастей (коп)')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('total_cost_kopecks')
                            ->label('Итого (коп)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vehicle.vin')
                    ->label('VIN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_cost_kopecks')
                    ->label('Сумма')
                    ->money('RUB', divideBy: 100),
                Tables\Columns\TextColumn::make('planned_at')
                    ->label('Дата')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ожидание',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAutoRepairOrders::route('/'),
            'create' => Pages\CreateAutoRepairOrder::route('/create'),
            'edit' => Pages\EditAutoRepairOrder::route('/{record}/edit'),
        ];
    }
}
