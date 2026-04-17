<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\StudioResource\Pages;

use App\Domains\Sports\Filament\Resources\StudioResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateStudio extends CreateRecord
{
    protected static string $resource = StudioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
