<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Application\DTOs;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     title="Fraud Check Data",
 *     description="Data Transfer Object for a fraud check operation",
 *     required={"transactionId", "amount", "userId", "ipAddress", "deviceFingerprint"}
 * )
 */
final class FraudCheckData extends Data
{
    public function __construct(
        /** @OA\Property(description="Unique transaction ID", example="txn_12345") */
        public readonly string $transactionId,
        /** @OA\Property(description="Transaction amount in cents", example=10000) */
        public readonly int $amount,
        /** @OA\Property(description="User ID performing the transaction", example=1) */
        public readonly int $userId,
        /** @OA\Property(description="IP address of the user", example="127.0.0.1") */
        public readonly string $ipAddress,
        /** @OA\Property(description="Device fingerprint of the user", example="abcdef123456") */
        public readonly string $deviceFingerprint,
        /** @OA\Property(description="Correlation ID for tracing", example="a1b2c3d4-e5f6-7890-1234-567890abcdef") */
        public readonly string $correlationId,
        /** @OA\Property(description="Additional metadata for scoring", type="object", example={"payment_method": "credit_card", "product_category": "electronics"}) */
        public readonly array $metadata,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            transactionId: $request->input('transaction_id'),
            amount: (int) $request->input('amount'),
            userId: (int) $request->input('user_id'),
            ipAddress: $request->ip(),
            deviceFingerprint: $request->input('device_fingerprint'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
            metadata: $request->input('metadata', []),
        );
    }
}
