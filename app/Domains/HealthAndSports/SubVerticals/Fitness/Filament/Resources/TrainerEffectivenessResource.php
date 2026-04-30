<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\TrainerEffectivenessModel;

final class TrainerEffectivenessResource extends Resource
{
    protected static ?string $model = TrainerEffectivenessModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Fitness Analytics';

    protected static ?int $navigationSort = 10;

    protected static ?string $label = 'Эффективность тренеров';

    protected static ?string $pluralLabel = 'Эффективность тренеров';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Общая оценка')
                    ->schema([
                        Forms\Components\TextInput::make('total_score')
                            ->numeric()
                            ->disabled()
                            ->suffix('/ 100')
                            ->label('Общий балл'),
                        Forms\Components\TextInput::make('effectiveness_level')
                            ->disabled()
                            ->label('Уровень эффективности'),
                        Forms\Components\Textarea::make('recommendations')
                            ->disabled()
                            ->rows(3)
                            ->label('Рекомендации'),
                    ]),

                Forms\Components\Section::make('Клиентские метрики (60%)')
                    ->schema([
                        Forms\Components\TextInput::make('retention_rate')
                            ->numeric()
                            ->disabled()
                            ->suffix('%')
                            ->label('Удержание клиентов'),
                        Forms\Components\TextInput::make('nps_score')
                            ->numeric()
                            ->disabled()
                            ->label('NPS Score'),
                        Forms\Components\TextInput::make('avg_check_per_client')
                            ->numeric()
                            ->disabled()
                            ->prefix('₽')
                            ->label('Средний чек'),
                        Forms\Components\TextInput::make('repeat_bookings_count')
                            ->numeric()
                            ->disabled()
                            ->label('Повторные записи'),
                        Forms\Components\TextInput::make('churn_rate')
                            ->numeric()
                            ->disabled()
                            ->suffix('%')
                            ->label('Отток клиентов'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Операционные метрики (25%)')
                    ->schema([
                        Forms\Components\TextInput::make('occupancy_rate')
                            ->numeric()
                            ->disabled()
                            ->suffix('%')
                            ->label('Загрузка'),
                        Forms\Components\TextInput::make('avg_group_attendance')
                            ->numeric()
                            ->disabled()
                            ->label('Средняя посещаемость групп'),
                        Forms\Components\TextInput::make('individual_sessions_count')
                            ->numeric()
                            ->disabled()
                            ->label('Индивидуальные тренировки'),
                        Forms\Components\TextInput::make('schedule_compliance')
                            ->numeric()
                            ->disabled()
                            ->suffix('%')
                            ->label('Соблюдение расписания'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Качественные метрики (15%)')
                    ->schema([
                        Forms\Components\TextInput::make('manager_score')
                            ->numeric()
                            ->disabled()
                            ->suffix('/ 10')
                            ->label('Оценка руководителя'),
                        Forms\Components\Toggle::make('methodology_compliance')
                            ->disabled()
                            ->label('Соблюдение методологии'),
                        Forms\Components\TextInput::make('progress_photos_count')
                            ->numeric()
                            ->disabled()
                            ->label('Фото прогресса'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Период')
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->disabled()
                            ->label('Начало периода'),
                        Forms\Components\DatePicker::make('period_end')
                            ->disabled()
                            ->label('Конец периода'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trainer.full_name')
                    ->label('Тренер')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->label('Балл')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                Tables\Columns\BadgeColumn::make('effectiveness_level')
                    ->colors([
                        'success' => 'A+',
                        'primary' => 'A',
                        'warning' => 'B',
                        'danger' => 'C',
                        'gray' => 'D',
                    ])
                    ->label('Уровень'),
                Tables\Columns\TextColumn::make('retention_rate')
                    ->label('Удержание')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1) . '%' : 'N/A'),
                Tables\Columns\TextColumn::make('nps_score')
                    ->label('NPS')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1) : 'N/A'),
                Tables\Columns\TextColumn::make('occupancy_rate')
                    ->label('Загрузка')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1) . '%' : 'N/A'),
                Tables\Columns\TextColumn::make('period_end')
                    ->label('Период')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('total_score', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('effectiveness_level')
                    ->options([
                        'A+' => 'A+ (90-100)',
                        'A' => 'A (80-89)',
                        'B' => 'B (65-79)',
                        'C' => 'C (50-64)',
                        'D' => 'D (<50)',
                    ])
                    ->label('Уровень эффективности'),
                Tables\Filters\Filter::make('top_performers')
                    ->query(fn ($query) => $query->whereIn('effectiveness_level', ['A+', 'A']))
                    ->label('Топ-тренеры'),
                Tables\Filters\Filter::make('needs_attention')
                    ->query(fn ($query) => $query->whereIn('effectiveness_level', ['C', 'D']))
                    ->label('Требуют внимания'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('recalculate')
                    ->label('Пересчитать')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            \Modules\Fitness\Application\Jobs\UpdateTrainerEffectivenessJob::dispatch(
                                $record->trainer_id
                            );
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Fitness\Filament\Resources\TrainerEffectivenessResource\Pages\ListTrainerEffectiveness::route('/'),
            'view' => \Modules\Fitness\Filament\Resources\TrainerEffectivenessResource\Pages\ViewTrainerEffectiveness::route('/{record}'),
        ];
    }
}
