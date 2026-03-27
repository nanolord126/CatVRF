<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelancerResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelancerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewFreelancer extends ViewRecord
{
    protected static string $resource = FreelancerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
