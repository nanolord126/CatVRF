<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Filament\Resources;

use App\Domains\Education\Models\CourseReview;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Class CourseReviewResource
 *
 * Part of the Education vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\Education\Courses\Filament\Resources
 */
final class CourseReviewResource extends Resource
{
    protected static ?string $model = CourseReview::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Обучение';
    protected static ?string $navigationLabel = 'Отзывы';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Название')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->label('Название')->searchable(),
                TextColumn::make('created_at')->label('Создан')->dateTime(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Education\Courses\Filament\Resources\CourseReviewResource\Pages\ListCourseReviews::route('/'),
        ];
    }
}