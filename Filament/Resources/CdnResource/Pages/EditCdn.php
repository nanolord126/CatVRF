<?php

declare(strict_types=1);

namespace App\Filament\Resources\CdnResource\Pages;

use App\Filament\Resources\CdnResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditCdn extends EditRecord
{
    protected static string $resource = CdnResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
