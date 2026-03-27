<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelanceServiceOfferResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelanceServiceOfferResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateFreelanceServiceOffer extends CreateRecord
{
    protected static string $resource = FreelanceServiceOfferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id'] = auth()->user()->tenant_id;

        return $data;
    }
}
