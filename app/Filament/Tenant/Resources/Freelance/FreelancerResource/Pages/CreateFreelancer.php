<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelancerResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelancerResource;
use App\Services\FraudControlService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateRecordFreelancer extends CreateRecord
{
    protected static string $resource = FreelancerResource::class;

    /**
     * КАНОН 2026 — FRAUD CHECK & UUID
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id'] = auth()->user()->tenant_id;

        // Пре-проверка на фрод при регистрации профиля специалиста
        app(FraudControlService::class)->check([
            'user_id' => auth()->id(),
            'operation' => 'freelancer_register',
            'correlation_id' => $data['correlation_id']
        ]);

        return $data;
    }
}
