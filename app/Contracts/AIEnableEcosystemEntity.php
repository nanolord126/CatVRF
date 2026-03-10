<?php

namespace App\Contracts;

/**
 * Contract for AI-Enabled Business Entities in the Global Ecosystem (Production 2026).
 *
 * Every vertical (Taxi, Clinic, Hotel, Restaurant, Education, Events, Sports, Vet)
 * MUST implement this interface to enable:
 * - Dynamic pricing based on demand heatmaps, weather, geolocation
 * - Trust/Fraud scoring for financial risk assessment
 * - Predictive checklists for operational planning
 *
 * The AI capabilities are integrated with:
 * @see \App\Services\AI\MLHelperService for demand prediction
 * @see \App\Models\AuditLog for compliance and traceability
 * @see \Modules\Wallet\Models\Wallet for financial transactions
 * @see \Modules\Payments\Models\Payment for payment processing
 *
 * @package App\Contracts
 * @author CatVRF AI Layer
 * @version 2026.1
 */
interface AIEnableEcosystemEntity
{
    /**
     * Get AI-adjusted price from the dynamic pricing engine.
     *
     * Price adjustment factors:
     * - Demand heatmaps in the geozone (real-time supply/demand ratio)
     * - Weather conditions (affects delivery speed, safety, demand for certain services)
     * - Time of day and seasonality patterns
     * - Historical price volatility for this entity
     * - Competitor pricing in the area
     *
     * Note: Adjusted prices typically vary ±20% from base price, max ±50% for extreme conditions.
     *
     * @param float $basePrice Base price before AI adjustment
     * @param array<string, mixed> $context Contextual data for adjustment:
     *        - 'location': string (address, city, or geozone ID)
     *        - 'time': string (ISO 8601 format, e.g., "2026-03-10T19:30:00Z")
     *        - 'weather': string (clear, rain, snow, fog)
     *        - 'day_of_week': string (monday-sunday)
     *        - 'is_holiday': bool (affects demand)
     *        - 'demand_percentile': float (0-1, current demand level)
     * @return float Adjusted price (base * multiplier), positive float
     *
     * @example Taxi surge pricing
     * ```php
     * $basePrice = 200.0; // RUB
     * $context = [
     *     'location' => 'Moscow, Red Square',
     *     'time' => '2026-03-10T19:00:00Z',  // evening
     *     'weather' => 'rain',
     *     'day_of_week' => 'friday',
     *     'demand_percentile' => 0.92  // 92% full capacity
     * ];
     * $adjustedPrice = $taxi->getAiAdjustedPrice($basePrice, $context);
     * // Returns ~260.0 (30% surge due to rain + evening peak)
     * ```
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float;

    /**
     * Get Trust Score for this entity (0-100).
     *
     * Trust Score is calculated from:
     * - Transaction history (count, volume, success rate)
     * - User ratings and reviews (weighted by recency)
     * - Chargebacks and complaints (last 12 months)
     * - Financial payment history (late payments, bounced checks)
     * - Behavioral fraud signals (unusual activity patterns)
     * - KYC/AML compliance status
     * - Identity verification level
     *
     * Score interpretation:
     * - 90-100: Trusted entity, eligible for 100% postpaid credit
     * - 70-89: Good entity, eligible for partial credit or delayed payment
     * - 50-69: Medium risk, requires upfront verification or guarantee
     * - 30-49: High risk, requires prepayment and ID verification
     * - 0-29: Very high risk, blocked from credit transactions
     *
     * @return int Trust score, 0-100 inclusive
     *
     * @example Seller trust assessment
     * ```php
     * $trustScore = $seller->getTrustScore();
     *
     * $paymentPolicy = match (true) {
     *     $trustScore >= 90   => 'postpaid',
     *     $trustScore >= 70   => 'net30',
     *     $trustScore >= 50   => 'prepay_50_percent',
     *     $trustScore >= 30   => 'prepay_100_percent',
     *     default             => 'blocked'
     * };
     * ```
     */
    public function getTrustScore(): int;

    /**
     * Generate predictive checklist for operational planning.
     *
     * Based on historical patterns and ML models, this returns:
     * - Preparation checklists for staff/masters before accepting orders
     * - Alerts about potential resource shortages or issues
     * - Optimization recommendations (efficiency, quality, safety)
     * - Resource planning (materials, tools, personnel needed)
     * - Quality assurance checkpoints
     *
     * @return array<int, array{
     *     id: string,
     *     title: string,
     *     description: string,
     *     priority: 'critical'|'high'|'medium'|'low',
     *     category: string,
     *     estimated_time_minutes: int,
     *     resources_needed: array<string>,
     *     risk_probability: float,
     *     completion_required: bool
     * }> Predictive checklist items sorted by priority
     *
     * @example Clinic pre-appointment checklist
     * ```php
     * $checklist = $clinic->generateAiChecklist();
     * // Returns:
     * [
     *     [
     *         'id' => 'sterile_check_001',
     *         'title' => 'Verify instrument sterilization batch',
     *         'description' => 'Check steam sterilizer logs for batch #2026-03-09',
     *         'priority' => 'critical',
     *         'category' => 'quality_assurance',
     *         'estimated_time_minutes' => 5,
     *         'resources_needed' => ['sterilization_logs'],
     *         'risk_probability' => 0.02,  // 2% chance of sterilization failure
     *         'completion_required' => true
     *     ],
     *     [
     *         'id' => 'supply_check_002',
     *         'title' => 'Antiseptic #3 running low',
     *         'description' => 'Only 2 bottles remaining, order 5 more immediately',
     *         'priority' => 'high',
     *         'category' => 'inventory',
     *         'estimated_time_minutes' => 30,
     *         'resources_needed' => ['supplier_contact'],
     *         'risk_probability' => 0.85,  // 85% chance of stockout today
     *         'completion_required' => false
     *     ]
     * ];
     * ```
     */
    public function generateAiChecklist(): array;
}
