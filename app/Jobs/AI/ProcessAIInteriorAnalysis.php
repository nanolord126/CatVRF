<?php

namespace App\Jobs\AI;

use App\Models\Tenant;
use App\Services\AI\AIInteriorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class ProcessAIInteriorAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tenantId;
    public $photoPath;
    public $preferences;
    public $sessionId;

    public function __construct(string $tenantId, string $photoPath, array $preferences, string $sessionId)
    {
        $this->tenantId = $tenantId;
        $this->photoPath = $photoPath;
        $this->preferences = $preferences;
        $this->sessionId = $sessionId;
    }

    public function handle(AIInteriorService $service)
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        
        // Выполнение тяжелого анализа
        $result = $service->analyzeRoom($tenant, $this->photoPath, $this->preferences);
        
        // Сохранение результата в Redis для фронтенда (Polling/WebSockets)
        Redis::set("ai_session:{$this->sessionId}", json_encode([
            'status' => 'completed',
            'result' => $result
        ]), 'EX', 3600); // 1 час жизни результата
    }
}
