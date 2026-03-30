<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreatePsychologist extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = PsychologistResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $correlationId = (string) Str::uuid();

            Log::channel('audit')->info('Creating Psychologist via Filament', [
                'data' => $data,
                'correlation_id' => $correlationId,
            ]);

            $data['correlation_id'] = $correlationId;
            $data['tenant_id'] = auth()->user()->tenant_id;

            return $data;
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
