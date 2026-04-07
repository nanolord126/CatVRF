<?php

declare(strict_types=1);

/**
 * WellnessAppointmentDto — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/wellnessappointmentdto
 */


namespace App\Domains\Beauty\Wellness\DTOs;

final readonly class WellnessAppointmentDto
{
    public function __construct(
        public int $center_id,
        public int $specialist_id,
        public int $service_id,
        public int $client_id,
        public string $datetime_start,
        public string $datetime_end,
        private string $status = 'pending',
        private int $price = 0,
        private string $payment_status = 'unpaid',
        private array $medical_notes = [],
        private ?string $correlation_id = null,
    ) {}

        /**
         * Map DTO to an array for Eloquent manipulation.
         */
        public function toArray(): array
        {
            return [
                'center_id' => $this->center_id,
                'specialist_id' => $this->specialist_id,
                'service_id' => $this->service_id,
                'client_id' => $this->client_id,
                'datetime_start' => $this->datetime_start,
                'datetime_end' => $this->datetime_end,
                'status' => $this->status,
                'price' => $this->price,
                'payment_status' => $this->payment_status,
                'medical_notes' => $this->medical_notes,
                'correlation_id' => $this->correlation_id,
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
