<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Entities;

use App\Domains\Beauty\Domain\ValueObjects\AppointmentId;
use App\Shared\Domain\Entities\Entity;
use App\Shared\Domain\ValueObjects\ClientId;
use InvalidArgumentException;

/**
 * Class Review
 *
 * Part of the Beauty vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Beauty\Domain\Entities
 */
final class Review extends Entity
{
    public function __construct(
        private int $id,
        private AppointmentId $appointmentId,
        private ClientId $clientId,
        private int $rating,
        private ?string $comment,
        private \DateTimeImmutable $createdAt,
    ) {
        $this->validateRating($rating);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAppointmentId(): AppointmentId
    {
        return $this->appointmentId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function validateRating(int $rating): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }
    }

    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointmentId->getValue(),
            'client_id' => $this->clientId->getValue(),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
