<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\HealthyFood\Models\DietPlan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class DietPlanResource extends Resource
{
    protected static ?string $model = DietPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'Diet Plans';
    protected static ?string $navigationGroup = 'HealthyFood';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('diet_type')
                    ->options([
                        'keto' => 'Keto',
                        'vegan' => 'Vegan',
                        'paleo' => 'Paleo',
                        'low-carb' => 'Low Carb',
                        'balanced' => 'Balanced',
                        'custom' => 'Custom',
                    ])
                    ->required(),

                TextInput::make('duration_days')
                    ->numeric()
                    ->required()
                    ->minValue(7)
                    ->maxValue(365),

                TextInput::make('daily_calories')
                    ->numeric()
                    ->required()
                    ->minValue(1000)
                    ->maxValue(5000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('diet_type')->badge()->sortable(),
                TextColumn::make('duration_days')->sortable(),
                TextColumn::make('daily_calories')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('diet_type')
                    ->options([
                        'keto' => 'Keto',
                        'vegan' => 'Vegan',
                        'paleo' => 'Paleo',
                        'balanced' => 'Balanced',
                    ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery()
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
