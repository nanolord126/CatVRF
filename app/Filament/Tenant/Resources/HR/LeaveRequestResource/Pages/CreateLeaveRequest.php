<?php

namespace App\Filament\Tenant\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Tenant\Resources\HR\LeaveRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        return $data;
    }
}
