<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AdCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateAdCampaign extends CreateRecord
{
    protected static string $resource = AdCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = Auth::user()?->tenant_id ?? 1;
        $data['correlation_id'] = (string) Str::uuid();
        $data['spent'] = $data['spent'] ?? 0;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
