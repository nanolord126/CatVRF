<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Sports\Fitness\Models\Gym;
use App\Domains\Sports\Fitness\Models\FitnessClass;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ClassService
{
    public function createClass(int $gymId, int $trainerId, string $name, string $description, string $classType, int $durationMinutes, int $maxParticipants, float $pricePerClass, string $correlationId): FitnessClass
    {


        try {
            $gym = Gym::findOrFail($gymId);

            $class = DB::transaction(function () use ($gym, $trainerId, $name, $description, $classType, $durationMinutes, $maxParticipants, $pricePerClass, $correlationId) {
                $class = FitnessClass::create([
                    'tenant_id' => $gym->tenant_id,
                    'gym_id' => $gym->id,
                    'trainer_id' => $trainerId,
                    'name' => $name,
                    'description' => $description,
                    'class_type' => $classType,
                    'duration_minutes' => $durationMinutes,
                    'max_participants' => $maxParticipants,
                    'price_per_class' => $pricePerClass,
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Fitness class created', [
                    'class_id' => $class->id,
                    'gym_id' => $gym->id,
                    'trainer_id' => $trainerId,
                    'correlation_id' => $correlationId,
                ]);

                return $class;
            });

            return $class;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to create fitness class', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function updateClass(FitnessClass $class, array $data, string $correlationId): void
    {


        try {
            DB::transaction(function () use ($class, $data, $correlationId) {
                $class->update(array_merge($data, ['correlation_id' => $correlationId]));

                Log::channel('audit')->info('Fitness class updated', [
                    'class_id' => $class->id,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to update fitness class', [
                'class_id' => $class->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
