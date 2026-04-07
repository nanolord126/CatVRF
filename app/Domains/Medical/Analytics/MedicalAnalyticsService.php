<?php

declare(strict_types=1);

namespace App\Domains\Medical\Analytics;

use App\Domains\Medical\Models\MedicalAppointment;
use App\Domains\Medical\Models\MedicalRecord;
use Illuminate\Support\Collection;

/**
 * Агрегирует аналитические метрики для медицинских клиник.
 *
 * Предоставляет статистику загруженности, топ диагнозов, retention
 * и сводные финансовые показатели за период.  Все запросы scoped
 * по tenant через глобальные scopes моделей.
 *
 * @see \App\Domains\Medical\Models\MedicalAppointment
 * @see \App\Domains\Medical\Models\MedicalRecord
 * @package App\Domains\Medical\Analytics
 */
final readonly class MedicalAnalyticsService
{
    /**
     * Количество дней, за которые собирается статистика по умолчанию.
     */
    private const DEFAULT_PERIOD_DAYS = 30;

    /**
     * Количество позиций в топе диагнозов.
     */
    private const TOP_DIAGNOSIS_LIMIT = 5;

    /**
     * Получить сводную статистику загруженности клиники.
     *
     * @param  int  $clinicId  Идентификатор клиники.
     * @param  int  $periodDays  Количество дней для расчёта (по умолчанию 30).
     * @return array{raw_stats: Collection, top_diagnosis: Collection, period: string, retention_rate: float}
     */
    public function getClinicStats(int $clinicId, int $periodDays = self::DEFAULT_PERIOD_DAYS): array
    {
        $since = now()->subDays($periodDays);

        $stats = MedicalAppointment::where('clinic_id', $clinicId)
            ->where('created_at', '>=', $since)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total_amount_kopecks), 0) as total_revenue')
            ->groupBy('status')
            ->get();

        $topDiagnosis = $this->getTopDiagnoses($clinicId, $since);

        return [
            'raw_stats' => $stats,
            'top_diagnosis' => $topDiagnosis,
            'period' => "last_{$periodDays}_days",
            'retention_rate' => $this->calculateRetention($clinicId, $since),
        ];
    }

    /**
     * Получить топ диагнозов по клинике за период.
     *
     * @param  int  $clinicId  Идентификатор клиники.
     * @param  \Illuminate\Support\Carbon  $since  Начало периода.
     * @return Collection  Коллекция [diagnosis_code, count].
     */
    private function getTopDiagnoses(int $clinicId, \Illuminate\Support\Carbon $since): Collection
    {
        return MedicalRecord::whereHas(
            'appointment',
            static fn ($query) => $query
                ->where('clinic_id', $clinicId)
                ->where('created_at', '>=', $since),
        )
            ->selectRaw('diagnosis_code, COUNT(*) as count')
            ->groupBy('diagnosis_code')
            ->orderByDesc('count')
            ->limit(self::TOP_DIAGNOSIS_LIMIT)
            ->get();
    }

    /**
     * Рассчитать Retention Rate клиники.
     *
     * Процент пациентов, у которых более одного приёма за период.
     *
     * @param  int  $clinicId  Идентификатор клиники.
     * @param  \Illuminate\Support\Carbon  $since  Начало периода.
     * @return float  Retention Rate в процентах (0.0 — 100.0).
     */
    private function calculateRetention(int $clinicId, \Illuminate\Support\Carbon $since): float
    {
        $totalPatients = MedicalAppointment::where('clinic_id', $clinicId)
            ->where('created_at', '>=', $since)
            ->distinct('patient_id')
            ->count('patient_id');

        if ($totalPatients === 0) {
            return 0.0;
        }

        $returningPatients = MedicalAppointment::where('clinic_id', $clinicId)
            ->where('created_at', '>=', $since)
            ->selectRaw('patient_id, COUNT(*) as visits')
            ->groupBy('patient_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return round(($returningPatients / $totalPatients) * 100, 1);
    }
}
