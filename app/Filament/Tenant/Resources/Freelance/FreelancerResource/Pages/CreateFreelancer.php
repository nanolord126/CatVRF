<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelancerResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateRecordFreelancer extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FreelancerResource::class;

        /**
         * КАНОН 2026 — FRAUD CHECK & UUID
         */
        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['correlation_id'] = (string) Str::uuid();
            $data['tenant_id'] = auth()->user()->tenant_id;

            // Пре-проверка на фрод при регистрации профиля специалиста
            app(FraudControlService::class)->check([
                'user_id' => auth()->id(),
                'operation' => 'freelancer_register',
                'correlation_id' => $data['correlation_id']
            ]);

            return $data;
        }
}
