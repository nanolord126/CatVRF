<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources;

use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Tables\Table;

final class ServiceReviewResource extends BaseOptimizedResource
{
    protected static ?string $model = null;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getPages(): array
    {
        return [];
    }

    /**
     * Relations to eager load for HomeServices
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
