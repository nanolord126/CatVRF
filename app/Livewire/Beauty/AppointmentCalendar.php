<?php declare(strict_types=1);

namespace App\Livewire\Beauty;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\Master;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Livewire-компонент: месячный календарь записей мастера (КАНОН 2026).
 * Отображает матрицу дней с количеством записей и их статусами.
 * Tenant scoping через global scope Appointment-модели.
 */
final class AppointmentCalendar extends Component
{
    // -------------------------------------------------------------------------
    // Props (переданы при монтировании)
    // -------------------------------------------------------------------------

    public int $salonId;
    public ?int $masterId = null;

    // -------------------------------------------------------------------------
    // Навигация по месяцам
    // -------------------------------------------------------------------------

    public int $year;
    public int $month;

    // -------------------------------------------------------------------------
    // Данные календаря
    // -------------------------------------------------------------------------

    /** Матрица недель: [[{day, date, appointments, isToday, isPast},...], ...] */
    public array $weeks = [];

    /** Статистика за месяц */
    public array $stats = [
        'total'     => 0,
        'completed' => 0,
        'cancelled' => 0,
        'pending'   => 0,
        'confirmed' => 0,
    ];

    /** Список мастеров для фильтра */
    public array $masters = [];

    /** Записи выбранного дня (для popover) */
    public array $selectedDayAppointments = [];
    public ?string $selectedDate = null;

    // -------------------------------------------------------------------------
    // Mount
    // -------------------------------------------------------------------------

    public function mount(int $salonId, ?int $masterId = null): void
    {
        $this->salonId  = $salonId;
        $this->masterId = $masterId;
        $this->year     = now()->year;
        $this->month    = now()->month;

        $this->loadMasters();
        $this->buildCalendar();
    }

    // -------------------------------------------------------------------------
    // Навигация
    // -------------------------------------------------------------------------

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
        $this->selectedDate = null;
        $this->selectedDayAppointments = [];
        $this->buildCalendar();
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
        $this->selectedDate = null;
        $this->selectedDayAppointments = [];
        $this->buildCalendar();
    }

    public function goToToday(): void
    {
        $this->year  = now()->year;
        $this->month = now()->month;
        $this->selectedDate = null;
        $this->selectedDayAppointments = [];
        $this->buildCalendar();
    }

    public function updatedMasterId(): void
    {
        $this->selectedDate = null;
        $this->selectedDayAppointments = [];
        $this->buildCalendar();
    }

    // -------------------------------------------------------------------------
    // Выбор дня
    // -------------------------------------------------------------------------

    public function selectDay(string $date): void
    {
        if ($this->selectedDate === $date) {
            // повторный клик — сбросить
            $this->selectedDate = null;
            $this->selectedDayAppointments = [];
            return;
        }

        $this->selectedDate = $date;
        $this->loadDayAppointments($date);

        $correlationId = (string) Str::uuid()->toString();
        $this->log->channel('audit')->info('BeautyCalendar: day selected', [
            'user_id'        => $this->auth->id(),
            'salon_id'       => $this->salonId,
            'master_id'      => $this->masterId,
            'date'           => $date,
            'correlation_id' => $correlationId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Построение матрицы
    // -------------------------------------------------------------------------

    private function buildCalendar(): void
    {
        $firstDay  = Carbon::create($this->year, $this->month, 1);
        $lastDay   = $firstDay->copy()->endOfMonth();
        $today     = Carbon::today();

        // Загружаем все записи за месяц одним запросом
        $query = Appointment::whereBetween('datetime_start', [
            $firstDay->startOfDay()->toDateTimeString(),
            $lastDay->endOfDay()->toDateTimeString(),
        ])->where('salon_id', $this->salonId);

        if ($this->masterId) {
            $query->where('master_id', $this->masterId);
        }

        $appointments = $query
            ->select('id', 'master_id', 'service_id', 'datetime_start', 'status', 'price')
            ->with(['master:id,full_name', 'service:id,name,duration_minutes'])
            ->get();

        // Индексируем по дате для быстрого поиска O(1)
        $byDate = $appointments->groupBy(fn ($a) => Carbon::parse($a->datetime_start)->format('Y-m-d'));

        // Считаем статистику за месяц
        $this->stats = [
            'total'     => $appointments->count(),
            'completed' => $appointments->where('status', 'completed')->count(),
            'cancelled' => $appointments->where('status', 'cancelled')->count(),
            'pending'   => $appointments->where('status', 'pending')->count(),
            'confirmed' => $appointments->where('status', 'confirmed')->count(),
        ];

        // Строим матрицу недель
        // Неделя начинается с понедельника (ISO-8601)
        $startOfCalendar = $firstDay->copy()->startOfWeek(Carbon::MONDAY);
        $endOfCalendar   = $lastDay->copy()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $currentWeek = [];
        $period = CarbonPeriod::create($startOfCalendar, $endOfCalendar);

        foreach ($period as $day) {
            $dateStr = $day->format('Y-m-d');
            $dayAppointments = $byDate->get($dateStr, collect());

            $currentWeek[] = [
                'day'             => $day->day,
                'date'            => $dateStr,
                'isCurrentMonth'  => $day->month === $this->month,
                'isToday'         => $day->isSameDay($today),
                'isPast'          => $day->lt($today),
                'appointments'    => $dayAppointments->count(),
                'statusBreakdown' => [
                    'confirmed' => $dayAppointments->where('status', 'confirmed')->count(),
                    'pending'   => $dayAppointments->where('status', 'pending')->count(),
                    'completed' => $dayAppointments->where('status', 'completed')->count(),
                    'cancelled' => $dayAppointments->where('status', 'cancelled')->count(),
                ],
            ];

            if (count($currentWeek) === 7) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
        }

        $this->weeks = $weeks;
    }

    private function loadDayAppointments(string $date): void
    {
        $query = Appointment::whereDate('datetime_start', $date)
            ->where('salon_id', $this->salonId);

        if ($this->masterId) {
            $query->where('master_id', $this->masterId);
        }

        $this->selectedDayAppointments = $query
            ->select('id', 'master_id', 'service_id', 'client_id', 'datetime_start', 'status', 'price')
            ->with([
                'master:id,full_name',
                'service:id,name,duration_minutes',
                'client:id,name,phone',
            ])
            ->orderBy('datetime_start')
            ->get()
            ->map(fn ($a) => [
                'id'         => $a->id,
                'time'       => Carbon::parse($a->datetime_start)->format('H:i'),
                'master'     => $a->master?->full_name ?? '—',
                'service'    => $a->service?->name ?? '—',
                'duration'   => $a->service?->duration_minutes ?? 0,
                'client'     => $a->client?->name ?? '—',
                'phone'      => $a->client?->phone ?? '—',
                'status'     => $a->status,
                'price'      => $a->price,
            ])
            ->toArray();
    }

    private function loadMasters(): void
    {
        $this->masters = Master::where('salon_id', $this->salonId)
            ->where('tenant_id', tenant('id'))
            ->select('id', 'full_name')
            ->orderBy('full_name')
            ->get()
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        return view('livewire.beauty.appointment-calendar', [
            'monthName'  => Carbon::create($this->year, $this->month, 1)->locale('ru')->isoFormat('MMMM YYYY'),
            'dayNames'   => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
        ]);
    }
}
