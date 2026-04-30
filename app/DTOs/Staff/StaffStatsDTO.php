<?php

declare(strict_types=1);

namespace App\DTOs\Staff;

/**
 * StaffStatsDTO — DTO для обновления статистики сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Immutable DTO для инкрементального обновления KPI и метрик.
 */
final readonly class StaffStatsDTO
{
    public function __construct(
        public ?int $ordersProcessed = null,
        public ?float $revenue = null,
        public ?int $customersServed = null,
        public ?float $satisfactionScore = null,
        public ?int $positiveReviews = null,
        public ?int $negativeReviews = null,
        public ?int $complaints = null,
        public ?int $compliments = null,
        public ?int $tasksCompleted = null,
        public ?int $tasksOverdue = null,
        public ?int $salesCount = null,
        public ?float $conversionRate = null,
        public ?float $upsellRate = null,
        public ?float $crossSellRate = null,
        public ?float $qualityScore = null,
        public ?float $speedScore = null,
        public ?float $accuracyScore = null,
        public ?int $attendanceDays = null,
        public ?int $absenceDays = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ordersProcessed: $data['orders_processed'] ?? $data['ordersProcessed'] ?? null,
            revenue: $data['revenue'] ?? null,
            customersServed: $data['customers_served'] ?? $data['customersServed'] ?? null,
            satisfactionScore: $data['satisfaction_score'] ?? $data['satisfactionScore'] ?? null,
            positiveReviews: $data['positive_reviews'] ?? $data['positiveReviews'] ?? null,
            negativeReviews: $data['negative_reviews'] ?? $data['negativeReviews'] ?? null,
            complaints: $data['complaints'] ?? null,
            compliments: $data['compliments'] ?? null,
            tasksCompleted: $data['tasks_completed'] ?? $data['tasksCompleted'] ?? null,
            tasksOverdue: $data['tasks_overdue'] ?? $data['tasksOverdue'] ?? null,
            salesCount: $data['sales_count'] ?? $data['salesCount'] ?? null,
            conversionRate: $data['conversion_rate'] ?? $data['conversionRate'] ?? null,
            upsellRate: $data['upsell_rate'] ?? $data['upsellRate'] ?? null,
            crossSellRate: $data['cross_sell_rate'] ?? $data['crossSellRate'] ?? null,
            qualityScore: $data['quality_score'] ?? $data['qualityScore'] ?? null,
            speedScore: $data['speed_score'] ?? $data['speedScore'] ?? null,
            accuracyScore: $data['accuracy_score'] ?? $data['accuracyScore'] ?? null,
            attendanceDays: $data['attendance_days'] ?? $data['attendanceDays'] ?? null,
            absenceDays: $data['absence_days'] ?? $data['absenceDays'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'orders_processed' => $this->ordersProcessed,
            'revenue' => $this->revenue,
            'customers_served' => $this->customersServed,
            'satisfaction_score' => $this->satisfactionScore,
            'positive_reviews' => $this->positiveReviews,
            'negative_reviews' => $this->negativeReviews,
            'complaints' => $this->complaints,
            'compliments' => $this->compliments,
            'tasks_completed' => $this->tasksCompleted,
            'tasks_overdue' => $this->tasksOverdue,
            'sales_count' => $this->salesCount,
            'conversion_rate' => $this->conversionRate,
            'upsell_rate' => $this->upsellRate,
            'cross_sell_rate' => $this->crossSellRate,
            'quality_score' => $this->qualityScore,
            'speed_score' => $this->speedScore,
            'accuracy_score' => $this->accuracyScore,
            'attendance_days' => $this->attendanceDays,
            'absence_days' => $this->absenceDays,
        ], fn ($value) => $value !== null);
    }
}
