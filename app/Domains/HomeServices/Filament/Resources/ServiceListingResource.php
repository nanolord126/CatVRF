<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

final class ServiceListingResource extends Resource
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
}
