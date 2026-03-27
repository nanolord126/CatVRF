<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource;
use App\Domains\Freelance\Models\FreelanceOrder;
use App\Domains\Freelance\Services\FreelanceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateFreelanceOrder extends CreateRecord
{
    protected static string $resource = FreelanceOrderResource::class;

    /**
     * КАНОН 2026 — Использование сервиса для создания заказа
     */
    protected function handleRecordCreation(array $data): FreelanceOrder
    {
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id'] = auth()->user()->tenant_id;

        return app(FreelanceService::class)->createOrder($data);
    }
}
