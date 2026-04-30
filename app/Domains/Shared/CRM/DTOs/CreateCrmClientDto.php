<?php

declare(strict_types=1);

namespace App\Domains\CRM\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для создания нового CRM-клиента.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CreateCrmClientDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public ?int $userId,
        public string $firstName,
        public ?string $lastName,
        public ?string $companyName,
        public ?string $email,
        public ?string $phone,
        public ?string $phoneSecondary,
        public string $clientType,
        public string $status,
        public ?string $source,
        public ?string $vertical,
        public array $addresses,
        public ?string $segment,
        public array $preferences,
        public array $specialNotes,
        public ?string $internalNotes,
        public array $verticalData,
        public ?string $avatarUrl,
        public string $preferredLanguage,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public array $tags = [],
    ) {}

    public static function fromRequest(Request $request, string $correlationId): self
    {
        return new self(
            tenantId: (int) ($request->user()->tenant_id ?? 1),
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId: $request->input('user_id') ? (int) $request->input('user_id') : null,
            firstName: (string) $request->input('first_name', ''),
            lastName: $request->input('last_name'),
            companyName: $request->input('company_name'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            phoneSecondary: $request->input('phone_secondary'),
            clientType: (string) $request->input('client_type', 'individual'),
            status: (string) $request->input('status', 'active'),
            source: $request->input('source'),
            vertical: $request->input('vertical'),
            addresses: (array) $request->input('addresses', []),
            segment: $request->input('segment'),
            preferences: (array) $request->input('preferences', []),
            specialNotes: (array) $request->input('special_notes', []),
            internalNotes: $request->input('internal_notes'),
            verticalData: (array) $request->input('vertical_data', []),
            avatarUrl: $request->input('avatar_url'),
            preferredLanguage: (string) $request->input('preferred_language', 'ru'),
            correlationId: $correlationId,
            idempotencyKey: $request->header('X-Idempotency-Key'),
            tags: (array) $request->input('tags', []),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'tags' => $this->tags,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'company_name' => $this->companyName,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_secondary' => $this->phoneSecondary,
            'client_type' => $this->clientType,
            'status' => $this->status,
            'source' => $this->source,
            'vertical' => $this->vertical,
            'addresses' => $this->addresses,
            'segment' => $this->segment,
            'preferences' => $this->preferences,
            'special_notes' => $this->specialNotes,
            'internal_notes' => $this->internalNotes,
            'vertical_data' => $this->verticalData,
            'avatar_url' => $this->avatarUrl,
            'preferred_language' => $this->preferredLanguage,
        ];
    }
}
