<?php

declare(strict_types=1);

namespace App\Domains\Garden\SubVerticals\Gardening\Filament\Resources;

use App\Domains\Gardening\Models\GardeningModels;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\Gardening\Filament\Resources\GardeningModelsResource\Pages\CreateGardeningModels;
use App\Domains\Gardening\Filament\Resources\GardeningModelsResource\Pages\EditGardeningModels;
use App\Domains\Gardening\Filament\Resources\GardeningModelsResource\Pages\ListGardeningModelss;
use Illuminate\Support\Str;

final class GardeningModelsResource extends BaseOptimizedResource
{
    protected static ?string $model = GardeningModels::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Gardening';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'draft' => 'Draft',
                ])
                ->default('active'),

            Forms\Components\Hidden::make('tenant_id')
                ->default(fn (): ?int => function_exists('tenant') && tenant() ? tenant()->id : null),

            Forms\Components\Hidden::make('correlation_id')
                ->default(fn (): string => Str::uuid()->toString()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'inactive' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'draft' => 'Draft',
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGardeningModelss::route('/'),
            'create' => CreateGardeningModels::route('/create'),
            'edit' => EditGardeningModels::route('/{record}/edit'),
        ];
    }

    /**
     * Relations to eager load for Gardening
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
