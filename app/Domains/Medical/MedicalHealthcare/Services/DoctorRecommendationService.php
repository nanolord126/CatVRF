<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services;

use App\Domains\Medical\MedicalHealthcare\DTOs\AIDiagnosticResultDto;
use App\Domains\Medical\Models\Doctor;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class DoctorRecommendationService
{
    public function __construct(
        private FraudControlService $fraud,
    ) {
    }

    public function recommendDoctors(AIDiagnosticResultDto $diagnostic, int $userId, bool $isB2B = false, callable $calculatePrice, callable $calculateMatchScore): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'doctor_recommendation',
            amount: 0,
            correlationId: $diagnostic->correlationId,
        );

        $cacheKey = "healthcare:recommendations:{$userId}:" . md5(json_encode($diagnostic->recommendedSpecialties));

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $recommendations = [];

        foreach ($diagnostic->recommendedSpecialties as $specialty) {
            $doctors = Doctor::where('specialty', $specialty)
                ->where('is_active', true)
                ->whereHas('clinic', function ($query) {
                    $query->where('is_active', true);
                })
                ->with(['clinic', 'reviews'])
                ->get();

            $scoredDoctors = $doctors->map(function ($doctor) use ($diagnostic, $userId, $isB2B, $calculatePrice, $calculateMatchScore) {
                $score = $calculateMatchScore($doctor, $diagnostic, $userId);
                $dynamicPrice = $calculatePrice($doctor, $diagnostic->urgencyLevel, $isB2B);

                return [
                    'doctor_id' => $doctor->id,
                    'name' => $doctor->name,
                    'specialty' => $doctor->specialty,
                    'clinic' => [
                        'id' => $doctor->clinic->id,
                        'name' => $doctor->clinic->name,
                        'address' => $doctor->clinic->address,
                        'rating' => floatval($doctor->clinic->rating ?? 4.5),
                    ],
                    'match_score' => $score,
                    'base_price' => floatval($doctor->consultation_price ?? 0),
                    'dynamic_price' => $dynamicPrice,
                    'available_slots' => $this->getAvailableSlots($doctor->id),
                    'rating' => floatval($doctor->rating ?? 4.5),
                    'experience_years' => intval($doctor->experience_years ?? 0),
                    'has_video_consultation' => boolval($doctor->has_video_consultation ?? false),
                    'is_flash_discount_available' => $this->isFlashDiscountAvailable($doctor->clinic_id),
                ];
            })
            ->sortByDesc('match_score')
            ->take(5)
            ->values();

            $recommendations[$specialty] = $scoredDoctors->toArray();
        }

        Cache::put($cacheKey, json_encode($recommendations), 1800);

        return $recommendations;
    }

    private function getAvailableSlots(int $doctorId): array
    {
        $today = today();
        $slots = [];

        for ($hour = 9; $hour <= 18; $hour++) {
            $slotTime = $today->setHour($hour)->setMinute(0)->format('Y-m-d H:i:s');

            $isBooked = \App\Domains\Medical\Models\MedicalAppointment::where('doctor_id', $doctorId)
                ->where('appointment_datetime', $slotTime)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->exists();

            if (!$isBooked) {
                $slots[] = $slotTime;
            }
        }

        return $slots;
    }

    private function isFlashDiscountAvailable(int $clinicId): bool
    {
        $loadFactor = $this->getClinicLoadFactor($clinicId);

        return $loadFactor < 0.3 && now()->hour >= 14 && now()->hour <= 17;
    }

    private function getClinicLoadFactor(int $clinicId): float
    {
        $todayAppointments = \App\Domains\Medical\Models\MedicalAppointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_datetime', today())
            ->count();

        $maxDailyCapacity = 100;

        return min(floatval($todayAppointments / $maxDailyCapacity), 1.0);
    }
}
