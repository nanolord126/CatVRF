<?php

declare(strict_types=1);

namespace Modules\Analytics\Filament\Resources\ExperimentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Analytics\Models\ExperimentVariant;

/**
 * Variants Relation Manager
 *
 * Manages experiment variants within the Experiment resource.
 */
class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variants';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(10)
                    ->default(fn () => chr(65 + $this->ownerRecord->variants()->count()))
                    ->helperText('A, B, C, etc.'),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\KeyValue::make('configuration')
                    ->keyLabel('Setting')
                    ->valueLabel('Value')
                    ->default([
                        'discount' => 0,
                        'message' => 'Default message',
                        'coupon_code' => null,
                    ])
                    ->required(),

                Forms\Components\TextInput::make('traffic_allocation')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->suffix('%')
                    ->required(),

                Forms\Components\Toggle::make('is_control')
                    ->label('Is Control Variant')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->badge()
                    ->color(fn ($record) => $record->is_control ? 'gray' : 'primary'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('configuration')
                    ->formatStateUsing(function ($state) {
                        $parts = [];
                        if (isset($state['discount']) && $state['discount'] > 0) {
                            $parts[] = "Discount: {$state['discount']}%";
                        }
                        if (isset($state['message'])) {
                            $parts[] = substr($state['message'], 0, 30) . '...';
                        }
                        return implode(' | ', $parts) ?: '-';
                    }),

                Tables\Columns\TextColumn::make('traffic_allocation')
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('sample_size')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\IconColumn::make('is_control')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
