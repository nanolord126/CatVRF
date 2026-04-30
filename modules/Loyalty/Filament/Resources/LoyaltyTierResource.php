<?php

declare(strict_types=1);

namespace Modules\Loyalty\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Loyalty\Infrastructure\Models\LoyaltyTierModel;

final class LoyaltyTierResource extends Resource
{
    protected static ?string $model = LoyaltyTierModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tier Information')
                    ->schema([
                        Forms\Components\Select::make('loyalty_program_id')
                            ->relationship('program', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Bronze, Silver, Gold'),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique()
                            ->placeholder('e.g., bronze, silver, gold'),
                        Forms\Components\Textarea::make('description')
                            ->rows(2),
                        Forms\Components\TextInput::make('color')
                            ->type('color')
                            ->default('#6B7280'),
                        Forms\Components\TextInput::make('icon')
                            ->placeholder('e.g., heroicon-o-star'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Tier Requirements')
                    ->schema([
                        Forms\Components\TextInput::make('min_points')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->required()
                            ->helperText('Minimum points to reach this tier'),
                        Forms\Components\TextInput::make('min_spend')
                            ->numeric()
                            ->step(0.01)
                            ->nullable()
                            ->helperText('Minimum total spend to reach this tier'),
                        Forms\Components\TextInput::make('min_visits')
                            ->numeric()
                            ->nullable()
                            ->helperText('Minimum visits to reach this tier'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Tier Benefits')
                    ->schema([
                        Forms\Components\TextInput::make('point_multiplier')
                            ->numeric()
                            ->step(0.01)
                            ->default(1.0)
                            ->required()
                            ->helperText('Point earning multiplier (e.g., 1.5x = 50% more points)'),
                        Forms\Components\TextInput::make('discount_percentage')
                            ->numeric()
                            ->step(0.0001)
                            ->default(0)
                            ->required()
                            ->helperText('Discount percentage (e.g., 0.10 = 10%)'),
                        Forms\Components\TagsInput::make('privileges')
                            ->placeholder('Add privileges')
                            ->helperText('e.g., late_checkout, free_upgrade, priority_seating'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\Textarea::make('metadata')
                            ->rows(3)
                            ->helperText('Additional metadata in JSON format'),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('program.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->toggleable(),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('min_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('point_multiplier')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => $state . 'x'),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => ($state * 100) . '%'),
                Tables\Columns\TextColumn::make('profiles_count')
                    ->counts('profiles')
                    ->label('Members'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('loyalty_program_id')
                    ->relationship('program', 'name'),
                Tables\Filters\TernaryFilter::make('is_active'),
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

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Loyalty\Filament\Resources\LoyaltyTierResource\Pages\ListLoyaltyTiers::route('/'),
            'create' => \Modules\Loyalty\Filament\Resources\LoyaltyTierResource\Pages\CreateLoyaltyTier::route('/create'),
            'edit' => \Modules\Loyalty\Filament\Resources\LoyaltyTierResource\Pages\EditLoyaltyTier::route('/{record}/edit'),
        ];
    }
}
