<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelancerResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelancerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFreelancers extends ListRecords
{
    protected static string $resource = FreelancerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
