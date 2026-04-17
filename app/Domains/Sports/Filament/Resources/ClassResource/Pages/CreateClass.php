<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\ClassResource\Pages;

use App\Domains\Sports\Filament\Resources\ClassResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateClass extends CreateRecord
{
    protected static string $resource = ClassResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
