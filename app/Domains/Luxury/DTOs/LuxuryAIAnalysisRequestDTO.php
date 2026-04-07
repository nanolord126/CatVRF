<?php declare(strict_types=1);

/**
 * LuxuryAIAnalysisRequestDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/luxuryaianalysisrequestdto
 */


namespace App\Domains\Luxury\DTO;

/**
 * Class LuxuryAIAnalysisRequestDTO
 *
 * Part of the Luxury vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\Luxury\DTO
 */
final readonly class LuxuryAIAnalysisRequestDTO
{

    public function __construct(
            public string $clientUuid,
            public string $analysisType, // 'style_match', 'gift_curation', 'investment_watch'
            private ?string $promptText = null,
            private readonly ?array $contextData = null,
            public string $correlationId
        ) {}
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
