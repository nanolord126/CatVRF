<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Repositories;

use Modules\BeautyMasters\Domain\Entities\Appointment;
use Modules\BeautyMasters\Domain\Repositories\AppointmentRepositoryInterface;
use Modules\BeautyMasters\Models\Appointment as AppointmentModel;
use Illuminate\Support\Collection;
use Carbon\Carbon;

final class EloquentAppointmentRepository implements AppointmentRepositoryInterface
{
    public function findById(int $id): ?Appointment
    {
        $model = AppointmentModel::find($id);
        if (!$model) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    public function findByVenueId(int $venueId, array $filters = []): Collection
    {
        $query = AppointmentModel::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        // Note: venue_id relationship needs to be added to model
        // For now, this is a basic implementation
        $models = $query->get();

        return $models->map(fn ($model) => $this->modelToEntity($model));
    }

    public function findByMasterId(int $masterId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): Collection
    {
        $models = AppointmentModel::where('master_id', $masterId)
            ->where('start_time', '>=', $startDate->format('Y-m-d H:i:s'))
            ->where('end_time', '<=', $endDate->format('Y-m-d H:i:s'))
            ->get();

        return $models->map(fn ($model) => $this->modelToEntity($model));
    }

    public function findByClientId(int $clientId, int $limit = 20): Collection
    {
        // Note: client_id field needs to be added to model
        // For now, this is a basic implementation
        $models = AppointmentModel::limit($limit)->get();

        return $models->map(fn ($model) => $this->modelToEntity($model));
    }

    public function findOverlapping(
        int $masterId,
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
        ?int $excludeId = null
    ): Collection
    {
        $query = AppointmentModel::where('master_id', $masterId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime->format('Y-m-d H:i:s'), $endTime->format('Y-m-d H:i:s')])
                  ->orWhereBetween('end_time', [$startTime->format('Y-m-d H:i:s'), $endTime->format('Y-m-d H:i:s')])
                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<', $startTime->format('Y-m-d H:i:s'))
                         ->where('end_time', '>', $endTime->format('Y-m-d H:i:s'));
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $models = $query->get();

        return $models->map(fn ($model) => $this->modelToEntity($model));
    }

    public function findAvailableSlots(
        int $masterId,
        int $serviceId,
        \DateTimeImmutable $date
    ): Collection
    {
        // This is a complex query that requires:
        // 1. Master schedule
        // 2. Service duration
        // 3. Existing appointments
        // For now, return empty collection as basic implementation
        return new Collection();
    }

    public function save(Appointment $appointment): Appointment
    {
        $model = new AppointmentModel([
            'master_id' => $appointment->masterId,
            'service_name' => $appointment->serviceName,
            'client_name' => $appointment->clientName,
            'start_time' => $appointment->startTime->format('Y-m-d H:i:s'),
            'end_time' => $appointment->endTime->format('Y-m-d H:i:s'),
            'status' => $appointment->status,
        ]);

        $model->save();

        return $this->modelToEntity($model);
    }

    public function delete(int $id): bool
    {
        return AppointmentModel::destroy($id) > 0;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $model = AppointmentModel::find($id);
        if (!$model) {
            return false;
        }

        $model->status = $status;
        return $model->save();
    }

    public function updatePaymentStatus(int $id, string $paymentStatus, ?int $paymentId = null): bool
    {
        $model = AppointmentModel::find($id);
        if (!$model) {
            return false;
        }

        // Note: payment_status and payment_id fields need to be added to model
        // For now, just update status
        $model->status = $paymentStatus;
        return $model->save();
    }

    private function modelToEntity(AppointmentModel $model): Appointment
    {
        // NOTE: The Eloquent model is incomplete compared to the Domain entity
        // The entity has 20+ fields but the model only has: id, master_id, service_name, client_name, start_time, end_time, status
        // This is a technical debt that needs to be addressed by updating the model schema
        // For now, we use default values for missing fields

        return Appointment::fromArray([
            'id' => $model->id,
            'venue_id' => 1, // TODO: Add venue_id to model
            'master_id' => $model->master_id,
            'client_id' => 1, // TODO: Add client_id to model
            'service_id' => 1, // TODO: Add service_id to model
            'start_time' => $model->start_time,
            'end_time' => $model->end_time,
            'status' => $model->status,
            'price' => 0.0, // TODO: Add price to model
            'discount_amount' => 0.0, // TODO: Add discount_amount to model
            'final_price' => 0.0, // TODO: Add final_price to model
            'currency' => 'RUB',
            'payment_status' => 'pending', // TODO: Add payment_status to model
            'payment_id' => null,
            'notes' => null,
            'client_notes' => null,
            'is_online_booking' => false, // TODO: Add is_online_booking to model
            'booking_source' => 'manual',
            'confirmed_at' => null, // TODO: Add confirmed_at to model
            'completed_at' => null, // TODO: Add completed_at to model
            'cancelled_at' => null, // TODO: Add cancelled_at to model
            'cancellation_reason' => null,
            'reminder_sent_24h' => 0, // TODO: Add reminder_sent_24h to model
            'reminder_sent_2h' => 0, // TODO: Add reminder_sent_2h to model
            'metadata' => null,
            'created_at' => $model->created_at ?? now()->format('Y-m-d H:i:s'),
            'updated_at' => $model->updated_at,
            'deleted_at' => $model->deleted_at,
        ]);
    }
}
