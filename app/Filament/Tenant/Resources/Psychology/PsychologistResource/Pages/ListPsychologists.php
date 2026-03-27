<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages;

use App\Filament\Tenant\Resources\Psychology\PsychologistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

final class ListPsychologists extends ListRecords
{
    protected static string $resource = PsychologistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        Log::channel('audit')->info('Accessing Psychologists list', [
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        return parent::getTableQuery()->withCount('bookings');
    }
}
