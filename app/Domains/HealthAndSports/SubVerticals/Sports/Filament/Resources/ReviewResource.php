<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use App\Domains\Sports\Filament\Resources\ReviewResource\Pages\CreateReview;
use App\Domains\Sports\Filament\Resources\ReviewResource\Pages\EditReview;
use App\Domains\Sports\Filament\Resources\ReviewResource\Pages\ListReviews;

final class ReviewResource extends BaseOptimizedResource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Отзывы';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Slider::make('rating')->label('Рейтинг')->min(1)->max(5)->required(),
            Forms\Components\TextInput::make('title')->label('Заголовок')->required(),
            Forms\Components\RichEditor::make('content')->label('Содержимое')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('studio.name')->label('Студия'),
                Tables\Columns\TextColumn::make('reviewer.email')->label('Автор'),
                Tables\Columns\TextColumn::make('rating')->label('Рейтинг')->formatStateUsing(fn ($state) => "⭐ $state"),
                Tables\Columns\IconColumn::make('verified_purchase')->label('Проверено')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'create' => CreateReview::route('/create'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()?->id);
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    /**
     * Relations to eager load for Sports
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
