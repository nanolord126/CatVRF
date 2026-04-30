<?php

declare(strict_types=1);

namespace App\Filament\B2B\Resources;

use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Business Group Resource
 *
 * Manage business groups (networks of tenants):
 * - Group settings
 * - Tenant assignments
 * - Aggregated analytics
 * - Group-level permissions
 */
final class BusinessGroupResource extends BaseOptimizedResource
{
    protected static ?string $model = null; // TODO: Create BusinessGroup model

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Business Groups';

    protected static ?string $navigationGroup = 'B2B Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Group Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),

                        Forms\Components\TextInput::make('max_tenants')
                            ->numeric()
                            ->default(100)
                            ->label('Max Tenants'),

                        Forms\Components\Select::make('subscription_plan')
                            ->options([
                                'basic' => 'Basic',
                                'professional' => 'Professional',
                                'enterprise' => 'Enterprise',
                            ])
                            ->default('basic')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Analytics Settings')
                    ->schema([
                        Forms\Components\Toggle::make('enable_analytics')
                            ->default(true)
                            ->label('Enable Group Analytics'),

                        Forms\Components\Toggle::make('enable_cross_tenant_reporting')
                            ->default(false)
                            ->label('Enable Cross-Tenant Reporting'),

                        Forms\Components\Toggle::make('enable_ml_insights')
                            ->default(false)
                            ->label('Enable ML Insights'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('Tenants')
                    ->counts('tenants')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->money('rub')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription_plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'gray',
                        'professional' => 'info',
                        'enterprise' => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->placeholder('All groups')
                    ->trueLabel('Active groups')
                    ->falseLabel('Inactive groups'),

                Tables\Filters\SelectFilter::make('subscription_plan')
                    ->options([
                        'basic' => 'Basic',
                        'professional' => 'Professional',
                        'enterprise' => 'Enterprise',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'tenants' => Tables\RelationManagers\RelationManager::class,
        ];
    }

    public static function getEagerLoading(): array
    {
        return ['tenants'];
    }
}
