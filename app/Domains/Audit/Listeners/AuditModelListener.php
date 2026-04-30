<?php

declare(strict_types=1);

namespace App\Domains\Audit\Listeners;

use App\Domains\Audit\Events\ModelCreatedEvent;
use App\Domains\Audit\Events\ModelUpdatedEvent;
use App\Domains\Audit\Events\ModelDeletedEvent;
use App\Domains\Audit\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * AuditModelListener — Automatic audit logging for model events.
 * Listens to domain events and records audit logs.
 */
final class AuditModelListener
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * Handle model created event
     */
    public function handleModelCreated(ModelCreatedEvent $event): void
    {
        $this->auditService->record(
            action: strtolower(class_basename($event->model)).'_created',
            subjectType: get_class($event->model),
            subjectId: $event->model->getKey(),
            oldValues: [],
            newValues: $event->model->getAttributes(),
            correlationId: $event->correlationId ?? Str::uuid()->toString(),
        );
    }

    /**
     * Handle model updated event
     */
    public function handleModelUpdated(ModelUpdatedEvent $event): void
    {
        $this->auditService->record(
            action: strtolower(class_basename($event->model)).'_updated',
            subjectType: get_class($event->model),
            subjectId: $event->model->getKey(),
            oldValues: $event->oldValues,
            newValues: $event->newValues,
            correlationId: $event->correlationId ?? Str::uuid()->toString(),
        );
    }

    /**
     * Handle model deleted event
     */
    public function handleModelDeleted(ModelDeletedEvent $event): void
    {
        $this->auditService->record(
            action: strtolower(class_basename($event->model)).'_deleted',
            subjectType: get_class($event->model),
            subjectId: $event->model->getKey(),
            oldValues: $event->deletedValues,
            newValues: [],
            correlationId: $event->correlationId ?? Str::uuid()->toString(),
        );
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(object $events): array
    {
        return [
            ModelCreatedEvent::class => 'handleModelCreated',
            ModelUpdatedEvent::class => 'handleModelUpdated',
            ModelDeletedEvent::class => 'handleModelDeleted',
        ];
    }
}
