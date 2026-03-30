<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateFreelanceOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FreelanceOrderResource::class;

        /**
         * КАНОН 2026 — Использование сервиса для создания заказа
         */
        protected function handleRecordCreation(array $data): FreelanceOrder
        {
            $data['correlation_id'] = (string) Str::uuid();
            $data['tenant_id'] = auth()->user()->tenant_id;

            return app(FreelanceService::class)->createOrder($data);
        }
}
