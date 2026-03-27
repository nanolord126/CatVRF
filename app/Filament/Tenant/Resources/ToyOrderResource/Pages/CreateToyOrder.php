<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToyOrderResource;

use App\Filament\Tenant\Resources\ToyOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateToyOrder extends CreateRecord
{
    protected static string $resource = \App\Filament\Tenant\Resources\ToyOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['order_number'] = 'TOY-ORD-' . strtoupper(Str::random(10));
        
        Log::channel('audit')->info('Creating Toy Order (Filament UI)', [
            'order_number' => $data['order_number'],
            'cid' => $data['correlation_id']
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('Toy Order Created (Filament UI)', [
            'id' => $this->record->id,
            'amount' => $this->record->total_amount
        ]);
    }
}
