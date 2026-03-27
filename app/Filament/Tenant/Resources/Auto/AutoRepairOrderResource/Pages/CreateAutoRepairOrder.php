<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource\Pages;

use App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

final class CreateAutoRepairOrder extends CreateRecord
{
    protected static string $resource = AutoRepairOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();
        
        $data['total_cost_kopecks'] = ($data['labor_cost_kopecks'] ?? 0) + ($data['parts_cost_kopecks'] ?? 0);

        Log::channel('audit')->info('Repair Order Creation Initiated', [
            'tenant_id' => $data['tenant_id'],
            'correlation_id' => $data['correlation_id'],
        ]);

        return $data;
    }
}
