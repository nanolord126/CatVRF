<?php
namespace App\Filament\Tenant\Resources\CampaignResource\Pages;
use App\Filament\Tenant\Resources\CampaignResource;
use Filament\Resources\Pages\ListRecords;

class ListCampaigns extends ListRecords {
    protected static string $resource = CampaignResource::class;
}
