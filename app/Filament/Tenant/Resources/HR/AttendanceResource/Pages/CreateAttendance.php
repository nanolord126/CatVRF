<?php

namespace App\Filament\Tenant\Resources\HR\AttendanceResource\Pages;

use App\Filament\Tenant\Resources\HR\AttendanceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = (string) Str::uuid();
        return $data;
    }
}
