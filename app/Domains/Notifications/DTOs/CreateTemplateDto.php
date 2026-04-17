<?php declare(strict_types=1);

namespace App\Domains\Notifications\DTOs;

final readonly class CreateTemplateDto
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $type,
        public string $channel,
        public string $subjectTemplate,
        public string $bodyTemplate,
        public ?array $variables = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            type: $data['type'],
            channel: $data['channel'],
            subjectTemplate: $data['subject_template'],
            bodyTemplate: $data['body_template'],
            variables: $data['variables'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'type' => $this->type,
            'channel' => $this->channel,
            'subject_template' => $this->subjectTemplate,
            'body_template' => $this->bodyTemplate,
            'variables' => $this->variables,
        ];
    }
}
