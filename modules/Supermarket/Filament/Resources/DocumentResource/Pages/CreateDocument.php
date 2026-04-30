<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Resources\DocumentResource\Pages;

use Modules\Supermarket\Filament\Resources\DocumentResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
