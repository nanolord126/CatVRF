<?php

declare(strict_types=1);

namespace App\Filament\CRM\Resources;

use Modules\CatCRM\Domain\Entities\ManagerKPI;
use Modules\CatCRM\Application\Services\ManagerKPIService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\CRM\Resources\ManagerKPIResource\Pages\CreateManagerKPI;
use App\Filament\CRM\Resources\ManagerKPIResource\Pages\EditManagerKPI;
use App\Filament\CRM\Resources\ManagerKPIResource\Pages\ListManagerKPIs;

/**
 * ManagerKPI Resource — KPI менеджеров в CRM
 * 
 * Управление KPI показателями менеджеров для всех вертикалей
 * Поддержка B2B/B2C контекста
 */
final class ManagerKPIResource extends Resource
{
    protected static ?string $model = ManagerKPI::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?string $navigationLabel = 'KPI Менеджеров';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        Select::make('manager_id')
                            ->label('Менеджер')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->required(),
                        
                        Select::make('business_group_id')
                            ->label('Филиал')
                            ->relationship('businessGroup', 'name')
                            ->searchable()
                            ->nullable(),
                        
                        Select::make('vertical_id')
                            ->label('Вертикаль')
                            ->relationship('vertical', 'name')
                            ->searchable()
                            ->nullable()
                            ->helperText('Оставьте пустым для всех вертикалей'),
                        
                        Select::make('period_type')
                            ->label('Тип периода')
                            ->options([
                                'daily' => 'Дневной',
                                'weekly' => 'Недельный',
                                'monthly' => 'Месячный',
                                'quarterly' => 'Квартальный',
                                'yearly' => 'Годовой',
                            ])
                            ->default('monthly')
                            ->required(),
                        
                        DatePicker::make('period_start')
                            ->label('Начало периода')
                            ->required(),
                        
                        DatePicker::make('period_end')
                            ->label('Конец периода')
                            ->required()
                            ->after('period_start'),
                        
                        Select::make('business_type')
                            ->label('Тип бизнеса')
                            ->options([
                                'b2b' => 'B2B',
                                'b2c' => 'B2C',
                                'both' => 'B2B и B2C',
                            ])
                            ->default('both')
                            ->required(),
                    ])
                    ->columns(3),
                
                Section::make('Цели KPI')
                    ->schema([
                        KeyValue::make('targets')
                            ->label('Цели')
                            ->keyLabel('Показатель')
                            ->valueLabel('Значение')
                            ->reorderable()
                            ->addable()
                            ->deletable()
                            ->helperText('Укажите цели в формате: {"tasks_completed": {"value": 50, "weight": 1.0}}')
                            ->default([
                                'tasks_completed' => ['value' => 50, 'weight' => 1.0],
                                'tasks_on_time' => ['value' => 45, 'weight' => 1.2],
                            ]),
                    ])
                    ->columns(1),
                
                Section::make('Статистика')
                    ->schema([
                        TextInput::make('tasks_assigned')
                            ->label('Назначено задач')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        
                        TextInput::make('tasks_completed')
                            ->label('Выполнено задач')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        
                        TextInput::make('tasks_on_time')
                            ->label('Вовремя выполнено')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        
                        TextInput::make('tasks_overdue')
                            ->label('Просрочено')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        
                        TextInput::make('score')
                            ->label('Общий балл')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('manager.name')
                    ->label('Менеджер')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('businessGroup.name')
                    ->label('Филиал')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('vertical.name')
                    ->label('Вертикаль')
                    ->searchable()
                    ->sortable(),
                
                BadgeColumn::make('period_type')
                    ->label('Период')
                    ->colors([
                        'daily' => 'gray',
                        'weekly' => 'blue',
                        'monthly' => 'green',
                        'quarterly' => 'orange',
                        'yearly' => 'purple',
                    ]),
                
                TextColumn::make('period_start')
                    ->label('Начало')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('period_end')
                    ->label('Конец')
                    ->date()
                    ->sortable(),
                
                BadgeColumn::make('business_type')
                    ->label('Тип')
                    ->colors([
                        'b2b' => 'blue',
                        'b2c' => 'green',
                        'both' => 'purple',
                    ]),
                
                TextColumn::make('score')
                    ->label('Балл')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)),
                
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'pending' => 'gray',
                        'in_progress' => 'blue',
                        'completed' => 'green',
                        'failed' => 'red',
                    ]),
                
                TextColumn::make('tasks_completed')
                    ->label('Выполнено')
                    ->sortable(),
                
                TextColumn::make('tasks_on_time')
                    ->label('Вовремя')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('manager_id')
                    ->label('Менеджер')
                    ->relationship('manager', 'name'),
                
                SelectFilter::make('business_group_id')
                    ->label('Филиал')
                    ->relationship('businessGroup', 'name'),
                
                SelectFilter::make('vertical_id')
                    ->label('Вертикаль')
                    ->relationship('vertical', 'name'),
                
                SelectFilter::make('period_type')
                    ->label('Тип периода')
                    ->options([
                        'daily' => 'Дневной',
                        'weekly' => 'Недельный',
                        'monthly' => 'Месячный',
                        'quarterly' => 'Квартальный',
                        'yearly' => 'Годовой',
                    ]),
                
                SelectFilter::make('business_type')
                    ->label('Тип бизнеса')
                    ->options([
                        'b2b' => 'B2B',
                        'b2c' => 'B2C',
                        'both' => 'B2B и B2C',
                    ]),
                
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'in_progress' => 'В процессе',
                        'completed' => 'Завершен',
                        'failed' => 'Провален',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('calculate')
                    ->label('Пересчитать')
                    ->icon('heroicon-o-calculator')
                    ->action(function (ManagerKPI $record) {
                        $record->calculateScore();
                        Notification::make()
                            ->title('KPI пересчитан')
                            ->success()
                            ->send();
                    }),
                Action::make('complete')
                    ->label('Завершить период')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ManagerKPI $record) {
                        $record->complete();
                        Notification::make()
                            ->title('Период завершен')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ManagerKPI $record) => $record->status !== 'completed'),
            ])
            ->bulkActions([
                // Bulk actions если нужны
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'history' => \Modules\CatCRM\Domain\Entities\ManagerKPIHistory::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManagerKPIs::route('/'),
            'create' => CreateManagerKPI::route('/create'),
            'edit' => EditManagerKPI::route('/{record}/edit'),
        ];
    }
}
