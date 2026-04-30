<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Modules\Analytics\Models\Experiment;
use Modules\Analytics\Models\ExperimentVariant;

/**
 * Experiment Filament Resource
 *
 * Admin interface for managing A/B testing experiments.
 * Provides CRUD operations, experiment lifecycle management,
 * and results visualization.
 */
class ExperimentResource extends Resource
{
    protected static ?string $model = Experiment::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Experiment Details')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for assignment'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->nullable(),

                        Forms\Components\Select::make('target_segment')
                            ->options([
                                'vip' => 'VIP Customers',
                                'high_clv' => 'High CLV',
                                'champions' => 'Champions',
                                'at_risk' => 'At Risk',
                                'new_customers' => 'New Customers',
                            ])
                            ->required(),

                        Forms\Components\KeyValue::make('clv_filters')
                            ->keyLabel('Filter')
                            ->valueLabel('Value')
                            ->nullable(),

                        Forms\Components\TextInput::make('traffic_percent')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(100)
                            ->suffix('%'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'running' => 'Running',
                                'paused' => 'Paused',
                                'finished' => 'Finished',
                            ])
                            ->default('draft')
                            ->required(),

                        Forms\Components\Select::make('primary_metric')
                            ->options([
                                'revenue_14d' => 'Revenue (14 days)',
                                'revenue_30d' => 'Revenue (30 days)',
                                'orders_count' => 'Orders Count',
                                'clv_delta' => 'CLV Delta',
                                'churn_prob_delta' => 'Churn Probability Delta',
                            ])
                            ->default('revenue_14d')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Timing')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_start_at')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('scheduled_end_at')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->disabled()
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('ended_at')
                            ->disabled()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Results')
                    ->schema([
                        Forms\Components\KeyValue::make('results')
                            ->keyLabel('Metric')
                            ->valueLabel('Value')
                            ->disabled(),

                        Forms\Components\TextInput::make('winning_variant_id')
                            ->disabled()
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record && $record->results !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'running',
                        'warning' => 'paused',
                        'danger' => 'finished',
                    ]),

                Tables\Columns\TextColumn::make('target_segment')
                    ->badge(),

                Tables\Columns\TextColumn::make('traffic_percent')
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('variants_count')
                    ->label('Variants')
                    ->counts('variants')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_sample_size')
                    ->label('Sample Size')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => number_format($record->getTotalSampleSize())),

                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'running' => 'Running',
                        'paused' => 'Paused',
                        'finished' => 'Finished',
                    ]),

                Tables\Filters\SelectFilter::make('target_segment')
                    ->options([
                        'vip' => 'VIP Customers',
                        'high_clv' => 'High CLV',
                        'champions' => 'Champions',
                        'at_risk' => 'At Risk',
                        'new_customers' => 'New Customers',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $record->start();
                    }),
                Tables\Actions\Action::make('pause')
                    ->label('Pause')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'running')
                    ->action(function ($record) {
                        $record->pause();
                    }),
                Tables\Actions\Action::make('finish')
                    ->label('Finish')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'running' || $record->status === 'paused')
                    ->action(function ($record) {
                        $record->finish();
                    }),
                Tables\Actions\Action::make('evaluate')
                    ->label('Evaluate Now')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'running')
                    ->action(function ($record) {
                        \Modules\Analytics\Infrastructure\Jobs\EvaluateExperimentJob::dispatch($record->id);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \Modules\Analytics\Filament\Resources\ExperimentResource\RelationManagers\VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Analytics\Filament\Resources\ExperimentResource\Pages\ListExperiments::route('/'),
            'create' => \Modules\Analytics\Filament\Resources\ExperimentResource\Pages\CreateExperiment::route('/create'),
            'view' => \Modules\Analytics\Filament\Resources\ExperimentResource\Pages\ViewExperiment::route('/{record}'),
            'edit' => \Modules\Analytics\Filament\Resources\ExperimentResource\Pages\EditExperiment::route('/{record}/edit'),
        ];
    }
}
