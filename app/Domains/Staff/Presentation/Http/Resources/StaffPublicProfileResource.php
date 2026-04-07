<?php

declare(strict_types=1);

namespace App\Domains\Staff\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * StaffPublicProfileResource — JSON-представление публичного профиля сотрудника.
 *
 * Трансформирует массив данных из GetStaffPublicProfileUseCase
 * в стандартный API-ответ с метаданными.
 */
final class StaffPublicProfileResource extends JsonResource
{
    /**
     * @param  array<string, mixed> $resource Данные профиля от UseCase.
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Трансформирует ресурс в массив для API-ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->getString('id'),
            'full_name'            => $this->getString('full_name'),
            'short_name'           => $this->getString('short_name'),
            'initials'             => $this->getString('initials'),
            'vertical'             => $this->getString('vertical'),
            'vertical_label'       => $this->getString('vertical_label'),
            'status'               => $this->getString('status'),
            'rating'               => $this->getFloat('rating'),
            'reviews_count'        => $this->getInt('reviews_count'),
            'avatar_url'           => $this->getStringOrNull('avatar_url'),
            'contact'              => $this->buildContact(),
        ];
    }

    /**
     * Добавляет мета-информацию к ответу.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'version'      => 'v1',
            ],
        ];
    }

    // ─── Вспомогательные методы ──────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function buildContact(): array
    {
        return [
            'email' => $this->maskEmail($this->getStringOrNull('email')),
            'phone' => $this->maskPhone($this->getStringOrNull('phone')),
        ];
    }

    /**
     * Маскирует email для публичного отображения (a***@domain.com).
     */
    private function maskEmail(?string $email): ?string
    {
        if ($email === null) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        $parts = explode('@', $email, 2);

        if (count($parts) !== 2) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        $localMasked = mb_substr($parts[0], 0, 1) . '***';

        return $localMasked . '@' . $parts[1];
    }

    /**
     * Маскирует телефон для публичного отображения (+7 *** ***-**-11).
     */
    private function maskPhone(?string $phone): ?string
    {
        if ($phone === null || mb_strlen($phone) < 4) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        $last4  = mb_substr($phone, -4);
        $prefix = mb_substr($phone, 0, 2);

        return $prefix . '* ***-***-' . mb_substr($last4, 0, 2) . '-' . mb_substr($last4, 2, 2);
    }

    private function getString(string $key): string
    {
        return (string) ($this->resource[$key] ?? '');
    }

    private function getStringOrNull(string $key): ?string
    {
        $val = $this->resource[$key] ?? null;

        return $val !== null ? (string) $val : null;
    }

    private function getFloat(string $key): float
    {
        return (float) ($this->resource[$key] ?? 0.0);
    }

    private function getInt(string $key): int
    {
        return (int) ($this->resource[$key] ?? 0);
    }
}
