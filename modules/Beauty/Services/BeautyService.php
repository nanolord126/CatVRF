<?php declare(strict_types=1);

namespace App\Modules\Beauty\Services;

use App\Modules\Beauty\Models\BeautySalon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DomainException;
use Throwable;

/**
 * Сервис управления красотой (салоны, услуги, мастера).
 * Production 2026.
 */
final class BeautyService
{
    /**
     * Создать салон красоты.
     */
    public function createSalon(
        int $tenantId,
        string $name,
        string $address,
        string $phone,
        string $email,
        ?string $description = null,
        array $workingHours = [],
        mixed $correlationId = null,
    ): BeautySalon {
        $correlationId ??= Str::uuid();

        try {
            Log::channel('audit')->info('beauty.service.salon.create.start', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'name' => $name,
            ]);

            $salon = DB::transaction(function () use (
                $tenantId,
                $name,
                $address,
                $phone,
                $email,
                $description,
                $workingHours,
                $correlationId,
            ) {
                return BeautySalon::create([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'address' => $address,
                    'phone' => $phone,
                    'email' => $email,
                    'description' => $description,
                    'working_hours' => $workingHours,
                    'correlation_id' => (string) $correlationId,
                    'tags' => ['beauty_salon', 'new'],
                    'is_verified' => false,
                    'rating' => 5.0,
                ]);
            });

            Log::channel('audit')->info('beauty.service.salon.create.success', [
                'correlation_id' => $correlationId,
                'salon_id' => $salon->id,
            ]);

            return $salon;
        } catch (Throwable $e) {
            Log::channel('audit')->critical('beauty.service.salon.create.error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Обновить информацию салона.
     */
    public function updateSalon(
        BeautySalon $salon,
        array $data,
        mixed $correlationId = null,
    ): BeautySalon {
        $correlationId ??= Str::uuid();

        try {
            Log::channel('audit')->info('beauty.service.salon.update.start', [
                'correlation_id' => $correlationId,
                'salon_id' => $salon->id,
            ]);

            $updated = DB::transaction(function () use ($salon, $data, $correlationId) {
                $salon->update([
                    'name' => $data['name'] ?? $salon->name,
                    'address' => $data['address'] ?? $salon->address,
                    'phone' => $data['phone'] ?? $salon->phone,
                    'email' => $data['email'] ?? $salon->email,
                    'description' => $data['description'] ?? $salon->description,
                    'working_hours' => $data['working_hours'] ?? $salon->working_hours,
                ]);
                return $salon->fresh();
            });

            Log::channel('audit')->info('beauty.service.salon.update.success', [
                'correlation_id' => $correlationId,
                'salon_id' => $updated->id,
            ]);

            return $updated;
        } catch (Throwable $e) {
            Log::channel('audit')->critical('beauty.service.salon.update.error', [
                'correlation_id' => $correlationId,
                'salon_id' => $salon->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить доступные слоты мастера на дату.
     */
    public function getAvailableSlots(
        int $salonId,
        int $masterId,
        int $serviceId,
        string $date,
    ): array {
        try {
            // Получить рабочие часы салона
            $salon = BeautySalon::findOrFail($salonId);
            
            $workingHours = $salon->working_hours;
            $dayOfWeek = strtolower(\Carbon\Carbon::parse($date)->format('l'));
            
            if (!isset($workingHours[$dayOfWeek])) {
                return []; // Салон закрыт в этот день
            }

            $hours = $workingHours[$dayOfWeek];
            $slots = [];

            // Генерировать 30-минутные слоты
            $current = \Carbon\Carbon::createFromFormat('H:i', $hours['open']);
            $end = \Carbon\Carbon::createFromFormat('H:i', $hours['close']);

            while ($current < $end) {
                // Проверить, не занят ли слот
                $isOccupied = \App\Modules\Beauty\Models\Appointment::where('master_id', $masterId)
                    ->whereDate('datetime', $date)
                    ->whereTime('datetime', $current->format('H:i'))
                    ->exists();

                if (!$isOccupied) {
                    $slots[] = [
                        'time' => $current->format('H:i'),
                        'available' => true,
                    ];
                }

                $current->addMinutes(30);
            }

            return $slots;
        } catch (Throwable $e) {
            Log::channel('audit')->critical('beauty.service.slots.error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить рейтинг салона.
     */
    public function getSalonRating(BeautySalon $salon): float
    {
        try {
            $reviews = \App\Modules\Beauty\Models\Review::where('salon_id', $salon->id)
                ->where('status', 'approved')
                ->avg('rating');

            return $reviews ?? 5.0;
        } catch (Throwable $e) {
            Log::channel('audit')->critical('beauty.service.rating.error', [
                'salon_id' => $salon->id,
                'error' => $e->getMessage(),
            ]);
            return 5.0;
        }
    }
}
