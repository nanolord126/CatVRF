<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrPropertyResource\Pages;

use App\Filament\Tenant\Resources\StrPropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditStrProperty extends EditRecord
{
    protected static string $resource = StrPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = request()->header('X-Correlation-ID', (string) Str::uuid());

        return $data;
    }
}
