<?php

namespace App\Services\HR;

use App\Models\HR\HRExchangeReview;
use App\Models\User;
use App\Models\HR\HRExchangeResponse;
use Illuminate\Support\Facades\DB;
use App\Services\Common\BusinessAdaptiveExperienceEngine;

class ReputationReportersService
{
    /**
     * Оценка по завершении задачи на HR Бирже
     */
    public function submitReview(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Создаем отзыв
            $review = HRExchangeReview::create([
                'hr_exchange_task_id' => $data['task_id'],
                'response_id' => $data['response_id'],
                'reviewer_id' => auth()->id(),
                'employee_id' => $data['employee_id'],
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'ai_tags' => $this->analyzeWithAI($data['comment'], $data['rating']),
            ]);

            // 2. Обновляем профиль сотрудника (Trust Score)
            $employee = User::find($data['employee_id']);
            $this->recomputeTrustScore($employee);

            // 3. (Опционально) Интеграция с адаптивным обучением
            if ($data['rating'] < 3) {
                // Если оценка низкая, AI может назначить повторный курс обучения
                // (new \App\Services\Common\StaffAdaptiveLearningManager())->assignEmergencyCourse($employee);
            }

            return $review;
        });
    }

    /**
     * Пересчет HR Trust Score на основе среднего рейтинга и завершенных задач
     */
    protected function recomputeTrustScore(User $user)
    {
        $stats = HRExchangeReview::where('employee_id', $user->id)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as tasks_count')
            ->first();

        $user->update([
            'hr_trust_score' => $stats->avg_rating ?? 5.0,
            'completed_tasks_count' => $stats->tasks_count ?? 0,
        ]);
    }

    /**
     * AI-анализ комментариев для извлечения тегов (Дисциплина, Лояльность и т.д.)
     */
    protected function analyzeWithAI(string $comment, int $rating)
    {
        // Имитация AI 2026: в реальности OpenAI/Embeddings
        $tags = [];
        if (str_contains(strtolower($comment), 'вовремя') || $rating > 4) $tags[] = 'Diligence';
        if (str_contains(strtolower($comment), 'вежлив')) $tags[] = 'Politeness';
        if ($rating == 5) $tags[] = 'Top Performer';
        
        return $tags;
    }
}
