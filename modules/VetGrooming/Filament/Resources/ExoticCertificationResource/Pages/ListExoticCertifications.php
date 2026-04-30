<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources\ExoticCertificationResource\Pages;

use Modules\VetGrooming\Filament\Resources\ExoticCertificationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListExoticCertifications extends ListRecords
{
    protected static string $resource = ExoticCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
