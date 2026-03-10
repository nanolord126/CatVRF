<?php

namespace App\Domains\Events\Services;

use App\Domains\Events\Models\Event;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EventService
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function createEvent(array $data): Event
    {
        try {
            return DB::transaction(function () use ($data) {
                $event = Event::create([...$data, 'tenant_id' => tenant()->id]);
                AuditLog::create([
                    'entity_type' => 'Event',
                    'entity_id' => $event->id,
                    'action' => 'create',
                    'correlation_id' => $this->correlationId,
                    'user_id' => auth()->id(),
                ]);
                return $event;
            });
        } catch (Throwable $e) {
            Log::error('EventService.createEvent failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function publishEvent(Event $event): Event
    {
        return DB::transaction(function () use ($event) {
            $event->update(['status' => 'published']);
            return $event;
        });
    }

    public function registerAttendee(Event $event, int $userId): bool
    {
        return DB::transaction(function () use ($event, $userId) {
            $event->attendees()->attach($userId);
            return true;
        });
    }
}
