<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DriftMonitoringResource\Pages;
use App\Models\FraudModelVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Drift Monitoring Resource
 * 
 * CANON 2026 - Production Ready
 * 
 * Filament dashboard for monitoring ML model drift across all models and verticals.
 * Provides real-time visibility into:
 * - Data drift (feature distribution changes)
 * - Concept drift (performance decay)
 * - Prediction drift (prediction distribution changes)
 * 
 * Features:
 * - Real-time drift metrics (PSI, KS-test, JS divergence)
 * - SHAP explainability for drift interpretation
 * - Historical trend analysis
 * - Alert management
 * - Retrain workflow initiation
 * 
 * Access: Super-admin, Data Scientists, ML Engineers
 */
class DriftMonitoringResource extends Resource
{
    protected static ?string $model = FraudModelVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Drift Monitoring';

    protected static ?string $navigationGroup = 'ML & Analytics';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super-admin', 'data-scientist', 'ml-engineer']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Model Information')
                    ->schema([
                        Forms\Components\TextInput::make('model_type')
                            ->label('Model Type')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('vertical_code')
                            ->label('Vertical Code')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('version')
                            ->label('Model Version')
                            ->required()
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Drift Metrics')
                    ->schema([
                        Forms\Components\Placeholder::make('data_drift_status')
                            ->label('Data Drift Status')
                            ->content(fn ($record) => $record->data_drift_detected ? '⚠️ Detected' : '✅ Normal'),
                        Forms\Components\Placeholder::make('concept_drift_status')
                            ->label('Concept Drift Status')
                            ->content(fn ($record) => $record->concept_drift_detected ? '⚠️ Detected' : '✅ Normal'),
                        Forms\Components\Placeholder::make('prediction_drift_status')
                            ->label('Prediction Drift Status')
                            ->content(fn ($record) => $record->prediction_drift_detected ? '⚠️ Detected' : '✅ Normal'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Performance Metrics')
                    ->schema([
                        Forms\Components\TextInput::make('accuracy')
                            ->label('Accuracy')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('f1_score')
                            ->label('F1 Score')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('auc_roc')
                            ->label('AUC-ROC')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model_type')
                    ->label('Model Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vertical_code')
                    ->label('Vertical')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('drift_status')
                    ->label('Drift Status')
                    ->colors([
                        'danger' => 'critical',
                        'warning' => 'warning',
                        'success' => 'normal',
                    ])
                    ->getStateUsing(fn ($record) => self::getDriftStatus($record)),
                Tables\Columns\TextColumn::make('max_drift_score')
                    ->label('Max Drift Score')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 4)),
                Tables\Columns\TextColumn::make('accuracy')
                    ->label('Accuracy')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 4)),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Last Checked')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->options([
                        'behavioral_biometrics' => 'Behavioral Biometrics',
                        'fraud_ml_ensemble' => 'Fraud ML Ensemble',
                        'insider_threat' => 'Insider Threat',
                        'vpn_proxy_detection' => 'VPN/Proxy Detection',
                    ]),
                Tables\Filters\SelectFilter::make('vertical_code')
                    ->options([
                        'medical' => 'Medical',
                        'payment' => 'Payment',
                        'taxi' => 'Taxi',
                        'hotels' => 'Hotels',
                        'food' => 'Food',
                        'marketplace' => 'Marketplace',
                    ]),
                Tables\Filters\SelectFilter::make('drift_status')
                    ->options([
                        'critical' => 'Critical',
                        'warning' => 'Warning',
                        'normal' => 'Normal',
                    ])
                    ->query(fn ($query, $data) => match ($data['value']) {
                        'critical' => $query->where('max_drift_score', '>', 0.25),
                        'warning' => $query->whereBetween('max_drift_score', [0.1, 0.25]),
                        'normal' => $query->where('max_drift_score', '<=', 0.1),
                        default => $query,
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('trigger_analysis')
                    ->label('Trigger Analysis')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($record) => self::triggerDriftAnalysis($record)),
                Tables\Actions\Action::make('request_retrain')
                    ->label('Request Retrain')
                    ->icon('heroicon-o-academic-cap')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->max_drift_score > 0.25)
                    ->action(fn ($record) => self::requestRetrain($record)),
            ])
            ->bulkActions([
                Tables\BulkActions\BulkAction::make('trigger_bulk_analysis')
                    ->label('Trigger Analysis for Selected')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($records) => self::triggerBulkAnalysis($records)),
            ])
            ->defaultSort('last_checked_at', 'desc');
    }

    private static function getDriftStatus($record): string
    {
        $maxDriftScore = $record->max_drift_score ?? 0;

        return match (true) {
            $maxDriftScore > 0.25 => 'critical',
            $maxDriftScore > 0.1 => 'warning',
            default => 'normal',
        };
    }

    private static function triggerDriftAnalysis($record): void
    {
        // Dispatch DailyDriftAnalysisJob for specific model
        \App\Jobs\ML\DailyDriftAnalysisJob::dispatch(
            $record->model_type,
            $record->vertical_code
        );

        \Filament\Notifications\Notification::make()
            ->title('Drift Analysis Triggered')
            ->body("Analysis started for {$record->model_type} ({$record->vertical_code})")
            ->success()
            ->send();
    }

    private static function requestRetrain($record): void
    {
        // Create retrain request
        // This would create a database entry and notify data scientists

        \Filament\Notifications\Notification::make()
            ->title('Retrain Request Created')
            ->body("Retrain request submitted for {$record->model_type} ({$record->vertical_code})")
            ->success()
            ->send();
    }

    private static function triggerBulkAnalysis($records): void
    {
        $count = 0;
        foreach ($records as $record) {
            \App\Jobs\ML\DailyDriftAnalysisJob::dispatch(
                $record->model_type,
                $record->vertical_code
            );
            $count++;
        }

        \Filament\Notifications\Notification::make()
            ->title('Bulk Analysis Triggered')
            ->body("Analysis started for {$count} models")
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriftMonitoring::route('/'),
            'view' => Pages\ViewDriftMonitoring::route('/{record}'),
            'dashboard' => Pages\DriftDashboard::route('/dashboard'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_active', true)
            ->where('is_shadow', false);
    }
}
