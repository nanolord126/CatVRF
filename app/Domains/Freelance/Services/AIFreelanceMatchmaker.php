<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIFreelanceMatchmaker extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Подобрать ТОП-5 исполнителей для конкретного заказа.
         */
        public function suggestFreelancers(FreelanceOrder $order): Collection
        {
            Log::channel('audit')->info('AI Matchmaker: Starting freelancer search', [
                'order_id' => $order->id,
                'budget' => $order->budget_kopecks,
                'correlation_id' => $order->correlation_id
            ]);

            return Freelancer::where('is_active', true)
                ->where('is_verified', true)
                ->get()
                ->map(function (Freelancer $freelancer) use ($order) {
                    $score = $this->calculateAIScore($freelancer, $order);
                    $freelancer->ai_match_score = $score;
                    return $freelancer;
                })
                ->sortByDesc('ai_match_score')
                ->take(5);
        }

        /**
         * Расчет ML-скора совместимости (0 - 1).
         */
        public function calculateAIScore(Freelancer $freelancer, FreelanceOrder $order): float
        {
            $score = 0.0;

            // 1. Совпадение навыков
            $orderKeywords = explode(' ', strtolower($order->title . ' ' . $order->requirements));
            $freelancerSkills = array_map('strtolower', $freelancer->skills ?? []);
            $matchedSkills = array_intersect($orderKeywords, $freelancerSkills);

            if (count($freelancerSkills) > 0) {
                $score += (count($matchedSkills) / count($freelancerSkills)) * 0.4;
            }

            // 2. Рейтинг и Опыт
            $score += ($freelancer->rating / 5.0) * 0.2;
            $score += min($freelancer->experience_years / 10.0, 1.0) * 0.1;

            // 3. Ценовая адекватность
            $estimatedHours = $order->budget_kopecks / max($freelancer->hourly_rate_kopecks, 1);
            if ($estimatedHours >= 10 && $estimatedHours <= 100) {
                $score += 0.2;
            }

            // 4. История
            if ($freelancer->completed_orders_count > 0) {
                $score += 0.1;
            }

            return round($score, 2);
        }
}
