<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FitnessGymService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function createGym(array $data, int $tenantId, string $correlationId): FitnessGym
        {


            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($data, $tenantId, $correlationId) {
                Log::channel('audit')->info('Creating fitness gym', ['correlation_id' => $correlationId]);

                return FitnessGym::create([
                    'tenant_id' => $tenantId,
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'geo_point' => $data['geo_point'] ?? null,
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
