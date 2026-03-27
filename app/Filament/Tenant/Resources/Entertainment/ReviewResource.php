<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment;

use App\Domains\EventPlanning\Entertainment\Models\Review;
use App\Filament\Tenant\Resources\Entertainment\ReviewResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — REVIEW RESOURCE (Entertainment Domain)
 */
final class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationGroup = 'Entertainment';

    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review Content')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->relationship('venue', 'name', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id))
                            ->required(),
                        Forms\Components\Select::make('event_id')
                            ->relationship('event', 'name', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id))
                            ->nullable(),
                        Forms\Components\TextInput::make('user_id')
                            ->numeric()
                            ->required(),
                        Forms\Components\RatingStar::make('rating')
                            ->required()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\Textarea::make('comment')
                            ->required()
                            ->maxLength(1000),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->numeric(1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
