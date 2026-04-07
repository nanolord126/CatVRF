<?php declare(strict_types=1);

namespace App\Domains\Fitness\DTOs;

use Illuminate\Http\Request;

/**
 * Class PurchaseMembershipDto
 *
 * Part of the Fitness vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Fitness\DTOs
 */
final readonly class PurchaseMembershipDto
{
    public function __construct(
        public int    $userId,
        public int    $gymId,
        public string $type,
        public int    $durationDays,
        public int    $priceKopecks,
        public bool   $isB2B,
        public string $correlationId,
        private ?string $idempotencyKey = null) {}

    public static function from(Request $request): self
    {
        $isB2B = $request->has('inn') && $request->has('business_card_id');

        return new self(
            userId:         (int) $request->user()->id,
            gymId:          (int) $request->input('gym_id'),
            type:           (string) $request->input('type', 'standard'),
            durationDays:   (int) $request->input('duration_days', 30),
            priceKopecks:   (int) $request->input('price'),
            isB2B:          $isB2B,
            correlationId:  (string) $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()),
            idempotencyKey: $request->input('idempotency_key'),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id'        => $this->userId,
            'gym_id'         => $this->gymId,
            'type'           => $this->type,
            'duration_days'  => $this->durationDays,
            'price'          => $this->priceKopecks,
            'is_b2b'         => $this->isB2B,
            'correlation_id' => $this->correlationId,
        ];
    }
}
