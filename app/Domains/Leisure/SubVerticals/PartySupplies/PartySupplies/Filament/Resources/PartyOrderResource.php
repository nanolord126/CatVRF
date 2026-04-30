<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\PartySupplies\Filament\Resources;

use App\Domains\PartySupplies\Models\PartyOrder;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\PartySupplies\Filament\Resources\PartyOrderResource\Pages\CreatePartyOrder;
use App\Domains\PartySupplies\Filament\Resources\PartyOrderResource\Pages\EditPartyOrder;
use App\Domains\PartySupplies\Filament\Resources\PartyOrderResource\Pages\ListPartyOrders;
use Illuminate\Support\Str;

final class PartyOrderResource extends BaseOptimizedResource
{
    protected static ?string $model = PartyOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'PartySupplies';

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
            'index' => ListPartyOrders::route('/'),
            'create' => CreatePartyOrder::route('/create'),
            'edit' => EditPartyOrder::route('/{record}/edit'),
        ];
    }

    /**
     * Relations to eager load for PartySupplies
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
