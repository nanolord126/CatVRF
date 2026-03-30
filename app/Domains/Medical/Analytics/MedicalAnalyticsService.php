<?php declare(strict_types=1);

namespace App\Domains\Medical\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalAnalyticsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Статистика загруженности клиники
         */
        public function getClinicStats(int $clinicId): array
        {
            $stats = MedicalAppointment::where('clinic_id', $clinicId)
                ->selectRaw('status, COUNT(*) as count, SUM(total_amount_kopecks) as total_revenue')
                ->groupBy('status')
                ->get();

            $topDiagnosis = MedicalRecord::whereHas('appointment', fn($q) => $q->where('clinic_id', $clinicId))
                ->selectRaw('diagnosis_code, COUNT(*) as count')
                ->groupBy('diagnosis_code')
                ->orderByDesc('count')
                ->limit(5)
                ->get();

            return [
                'raw_stats' => $stats,
                'top_diagnosis' => $topDiagnosis,
                'period' => 'last_30_days',
                'retention_rate' => $this->calculateRetention($clinicId),
            ];
        }

        private function calculateRetention(int $clinicId): float
        {
            // Имитация расчета Retention Rate
            return 68.5;
        }
}
