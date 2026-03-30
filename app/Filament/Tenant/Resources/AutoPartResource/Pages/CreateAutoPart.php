<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoPartResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateAutoPart extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = AutoPartResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['tenant_id'] = tenant()->id;
            $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();

            return $data;
        }

        protected function afterCreate(): void
        {
            activity()
                ->performedBy(auth()->user())
                ->on($this->record)
                ->withProperty('correlation_id', $this->record->correlation_id)
                ->log('Auto part added to STO inventory');
        }
}
