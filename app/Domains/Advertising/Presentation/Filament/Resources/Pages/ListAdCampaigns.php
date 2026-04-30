<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AdCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdCampaigns extends ListRecords
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
