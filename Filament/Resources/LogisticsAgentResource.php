<?php

declare(strict_types=1);

namespace Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Facades\Cache;
use Modules\GeoLogistics\Services\LogisticsInferenceService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;

class LogisticsAgentResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-robot';

    protected static ?string $navigationLabel = 'Logistics Agent';

    protected static ?string $navigationGroup = 'AI & Analytics';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Agent Configuration')
                    ->description('Configure the autonomous logistics agent')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->options(fn () => Cache::get('active_tenants', []))
                            ->required()
                            ->searchable(),

                        Forms\Components\Toggle::make('enable_autonomous')
                            ->label('Enable Autonomous Mode')
                            ->helperText('When enabled, agent will execute actions automatically without human approval')
                            ->default(false)
                            ->reactive(),

                        Forms\Components\Select::make('llm_provider')
                            ->label('LLM Provider')
                            ->options([
                                'openai' => 'OpenAI (GPT-4)',
                                'anthropic' => 'Anthropic (Claude)',
                                'grok' => 'Grok',
                                'rule_based' => 'Rule-Based (No LLM)',
                            ])
                            ->default('openai')
                            ->required(),

                        Forms\Components\Select::make('llm_model')
                            ->label('LLM Model')
                            ->options([
                                'gpt-4-turbo' => 'GPT-4 Turbo',
                                'gpt-4' => 'GPT-4',
                                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                            ])
                            ->default('gpt-4-turbo')
                            ->required(),

                        Forms\Components\TextInput::make('max_auto_actions_per_hour')
                            ->label('Max Auto Actions per Hour')
                            ->numeric()
                            ->default(10)
                            ->helperText('Safety limit for autonomous actions'),

                        Forms\Components\Toggle::make('enable_notifications')
                            ->label('Enable Notifications')
                            ->helperText('Send notifications for agent actions')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'supervised',
                        'danger' => 'error',
                    ])
                    ->sortable(),

                IconColumn::make('autonomous_mode')
                    ->label('Autonomous')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),

                TextColumn::make('llm_provider')
                    ->label('LLM Provider')
                    ->sortable(),

                TextColumn::make('total_observations')
                    ->label('Observations')
                    ->sortable(),

                TextColumn::make('total_actions')
                    ->label('Actions')
                    ->sortable(),

                TextColumn::make('last_cycle_time')
                    ->label('Last Cycle (s)')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'supervised' => 'Supervised',
                        'error' => 'Error',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('run_cycle')
                    ->label('Run Cycle')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function (array $data) {
                        // Trigger agent cycle
                        $tenantId = $data['tenant_id'];
                        $inferenceService = app(LogisticsInferenceService::class);
                        $result = $inferenceService->runAgentCycle(
                            tenantId: $tenantId,
                            requireApproval: ! $data['enable_autonomous'] ?? false,
                            useLLM: true
                        );

                        Notification::make()
                            ->title('Agent Cycle Executed')
                            ->body('Actions executed: '.($result->data['actions_count'] ?? 0))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable_autonomous')
                        ->label('Enable Autonomous')
                        ->requiresConfirmation()
                        ->action(function () {
                            // Bulk enable autonomous mode
                        }),
                ]),
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
            'index' => ListRecords::route('/'),
            'create' => CreateRecord::route('/create'),
            'edit' => EditRecord::route('/{record}/edit'),
            'view' => ViewRecord::route('/{record}'),
        ];
    }
}
