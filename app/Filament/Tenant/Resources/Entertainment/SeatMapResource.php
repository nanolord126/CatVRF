<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment;

use App\Domains\EventPlanning\Entertainment\Models\SeatMap;
use App\Filament\Tenant\Resources\Entertainment\SeatMapResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — SEATMAP RESOURCE (Entertainment Domain)
 */
final class SeatMapResource extends Resource
{
    protected static ?string $model = SeatMap::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationGroup = 'Entertainment';

    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Layout Configuration')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->relationship('venue', 'name', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id))
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\JsonEditor::make('layout')
                            ->label('Arena Seating Plan (JSON)')
                            ->required(),
                        Forms\Components\TextInput::make('uuid')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn () => (string) Str::uuid()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('venue.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('venue_id')
                    ->relationship('venue', 'name', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeatMaps::route('/'),
            'create' => Pages\CreateSeatMap::route('/create'),
            'edit' => Pages\EditSeatMap::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
