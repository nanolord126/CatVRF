<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Presentation\B2C\Livewire;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Psr\Log\LoggerInterface;

/**
 * MyAppointments — Livewire-компонент для User Cabinet.
 *
 * Показывает список записей текущего пользователя (B2C):
 * предстоящие, прошедшие, отменённые.
 * Позволяет отменить запись (если до начала > 2 часа).
 *
 * CANON 2026: no facades, correlation_id, audit logging.
 *
 * @package CatVRF\Beauty\Presentation\B2C
 * @version 2026.1
 */
final class MyAppointments extends Component
{
    use WithPagination;

    /** @var string Текущий фильтр: upcoming, past, cancelled */
    public string $filter = 'upcoming';

    /** @var string|null */
    public ?string $errorMessage = null;

    /** @var string|null */
    public ?string $successMessage = null;

    /** @var int Кол-во элементов на странице */
    public int $perPage = 10;

    /**
     * Получить отфильтрованные записи.
     */
    public function getAppointmentsProperty(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        /** @var Guard $guard */
        $guard = app(Guard::class);

        $userId = (int) ($guard->id() ?? 0);

        $query = Appointment::query()
            ->where('client_id', $userId)
            ->with(['master', 'service', 'salon'])
            ->orderByDesc('start_at');

        return match ($this->filter) {
            'upcoming'  => $query->where('status', 'confirmed')
                ->where('start_at', '>=', now())
                ->paginate($this->perPage),
            'past'      => $query->whereIn('status', ['completed', 'confirmed'])
                ->where('start_at', '<', now())
                ->paginate($this->perPage),
            'cancelled' => $query->where('status', 'cancelled')
                ->paginate($this->perPage),
            default     => $query->paginate($this->perPage),
        };
    }

    /**
     * Переключить фильтр.
     */
    public function setFilter(string $filter): void
    {
        $allowedFilters = ['upcoming', 'past', 'cancelled'];

        if (!in_array($filter, $allowedFilters, true)) {
            return;
        }

        $this->filter = $filter;
        $this->resetPage();
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    /**
     * Отменить запись.
     *
     * Допускается только если до начала > 2 часов.
     * В ином случае — штраф (логика в UseCase).
     */
    public function cancelAppointment(int $appointmentId): void
    {
        $correlationId = Str::uuid()->toString();

        try {
            /** @var Guard $guard */
            $guard = app(Guard::class);

            $userId = (int) ($guard->id() ?? 0);

            $appointment = Appointment::query()
                ->where('id', $appointmentId)
                ->where('client_id', $userId)
                ->whereIn('status', ['confirmed', 'pending'])
                ->firstOrFail();

            $hoursUntilStart = now()->diffInHours($appointment->start_at, false);

            if ($hoursUntilStart < 2) {
                $this->errorMessage = 'Отмена возможна не позднее чем за 2 часа до начала. Может взиматься штраф.';
                return;
            }

            $appointment->update([
                'status'         => 'cancelled',
                'cancelled_at'   => now(),
                'cancel_reason'  => 'Отменено клиентом из личного кабинета',
                'correlation_id' => $correlationId,
            ]);

            /** @var LoggerInterface $logger */
            $logger = app(LoggerInterface::class);
            $logger->info('Beauty B2C: запись отменена из User Cabinet', [
                'appointment_id' => $appointmentId,
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
            ]);

            $this->successMessage = 'Запись успешно отменена.';
            $this->errorMessage = null;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $this->errorMessage = 'Запись не найдена или уже отменена.';
        } catch (\Throwable $e) {
            $this->errorMessage = 'Ошибка отмены: ' . $e->getMessage();

            /** @var LoggerInterface $logger */
            $logger = app(LoggerInterface::class);
            $logger->error('Beauty B2C: ошибка отмены записи', [
                'appointment_id' => $appointmentId,
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    /**
     * Рендер компонента.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('beauty::livewire.my-appointments', [
            'appointments'   => $this->appointments,
            'filter'         => $this->filter,
            'errorMessage'   => $this->errorMessage,
            'successMessage' => $this->successMessage,
        ]);
    }
}
