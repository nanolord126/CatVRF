<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LuxuryProductResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateLuxuryProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = LuxuryProductResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['correlation_id'] = (string) Str::uuid();

            Log::channel('audit')->info('Creating Luxury Product via Filament', [
                'sku' => $data['sku'] ?? 'N/A',
                'user_id' => auth()->id(),
                'correlation_id' => $data['correlation_id'],
            ]);

            return $data;
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
