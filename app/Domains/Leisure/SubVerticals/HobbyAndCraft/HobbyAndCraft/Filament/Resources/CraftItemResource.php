<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\HobbyAndCraft\Filament\Resources;

use App\Domains\HobbyAndCraft\Models\CraftItem;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\HobbyAndCraft\Filament\Resources\CraftItemResource\Pages\CreateCraftItem;
use App\Domains\HobbyAndCraft\Filament\Resources\CraftItemResource\Pages\EditCraftItem;
use App\Domains\HobbyAndCraft\Filament\Resources\CraftItemResource\Pages\ListCraftItems;
use Illuminate\Support\Str;

final class CraftItemResource extends BaseOptimizedResource
{
    protected static ?string $model = CraftItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'HobbyAndCraft';

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
            'index' => ListCraftItems::route('/'),
            'create' => CreateCraftItem::route('/create'),
            'edit' => EditCraftItem::route('/{record}/edit'),
        ];
    }

    /**
     * Relations to eager load for HobbyAndCraft
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
