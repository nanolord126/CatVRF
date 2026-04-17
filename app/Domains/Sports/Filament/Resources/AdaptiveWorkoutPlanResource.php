<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources;

use App\Domains\Sports\Filament\Resources\AdaptiveWorkoutPlanResource\Pages;
use App\Domains\Sports\Filament\Resources\AdaptiveWorkoutPlanResource\Pages\ListAdaptiveWorkoutPlans;
use App\Domains\Sports\Filament\Resources\AdaptiveWorkoutPlanResource\Pages\ViewAdaptiveWorkoutPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class AdaptiveWorkoutPlanResource extends Resource
{
    protected static ?string $model = \App\Domains\Sports\Models\AdaptiveWorkoutPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Sports';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Information')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->required()
                            ->numeric(),
                        Forms\Components\Textarea::make('plan_data')
                            ->required()
                            ->rows(10),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\TextInput::make('correlation_id')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('correlation_id')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdaptiveWorkoutPlans::route('/'),
            'view' => ViewAdaptiveWorkoutPlan::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name'];
    }
}
