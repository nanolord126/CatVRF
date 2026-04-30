<?php

declare(strict_types=1);

namespace App\Domains\CRM\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для создания CRM-автоматизации (триггерной кампании).
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CreateCrmAutomationDto
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public ?string $description,
        public ?string $vertical,
        public bool $isActive,
        public string $triggerType,
        public array $triggerConfig,
        public string $actionType,
        public array $actionConfig,
        public string $delayType,
        public int $delayMinutes,
        public string $correlationId,
        public array $tags = [],
    ) {}

    public static function fromRequest(Request $request, string $correlationId): self
    {
        return new self(
            tenantId: (int) ($request->user()->tenant_id ?? 1),
            name: (string) $request->input('name', ''),
            description: $request->input('description'),
            vertical: $request->input('vertical'),
            isActive: (bool) $request->input('is_active', false),
            triggerType: (string) $request->input('trigger_type', 'custom_date'),
            triggerConfig: (array) $request->input('trigger_config', []),
            actionType: (string) $request->input('action_type', 'send_email'),
            actionConfig: (array) $request->input('action_config', []),
            delayType: (string) $request->input('delay_type', 'immediate'),
            delayMinutes: (int) $request->input('delay_minutes', 0),
            correlationId: $correlationId,
            tags: (array) $request->input('tags', []),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'description' => $this->description,
            'vertical' => $this->vertical,
            'is_active' => $this->isActive,
            'trigger_type' => $this->triggerType,
            'trigger_config' => $this->triggerConfig,
            'action_type' => $this->actionType,
            'action_config' => $this->actionConfig,
            'delay_type' => $this->delayType,
            'delay_minutes' => $this->delayMinutes,
            'correlation_id' => $this->correlationId,
            'tags' => $this->tags,
        ];
    }
}
