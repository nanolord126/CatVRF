<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\ReturnResource\Pages;

use App\Domains\Supermarket\Filament\Resources\ReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

/**
 * CreateReturn - Страница создания возврата.
 */
class CreateReturn extends CreateRecord
{
    protected static string $resource = ReturnResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
