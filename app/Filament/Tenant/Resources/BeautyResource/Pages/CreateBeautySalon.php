<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeautyResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateBeautySalon extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BeautyResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();
            $data['correlation_id'] = Str::uuid()->toString();

            if (session()->has('business_card_id')) {
                $data['business_group_id'] = session('business_card_id');
            }

            return $data;
        }
}
