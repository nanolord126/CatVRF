<?php
declare(strict_types=1);

namespace App\Domains\Events\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use App\Domains\Events\Models\Event;

final readonly class EventService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function createEvent(array $data, string $correlationId): Event
    {
        return DB::transaction(function () use ($data, $correlationId) {
            Log::channel('audit')->info("СОЗДАНИЕ МЕРОПРИЯТИЯ", ["correlation_id" => $correlationId, "data" => $data]);
            
            FraudControlService::check($data, $correlationId);

            $event = Event::create([
                "tenant_id" => tenant("id") ?? 1,
                "correlation_id" => $correlationId,
                "title" => $data["title"] ?? "Новое событие",
                "tags" => []
            ]);

            Log::channel('audit')->info("МЕРОПРИЯТИЕ СОЗДАНО", ["correlation_id" => $correlationId, "id" => $event->id]);

            return $event;
        });
    }
}
