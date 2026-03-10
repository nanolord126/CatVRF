<?php

namespace App\Domains\Communication\Services;

use App\Domains\Communication\Models\Message;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CommunicationService
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function sendMessage(array $data): Message
    {
        try {
            return DB::transaction(function () use ($data) {
                $message = Message::create([...$data, 'tenant_id' => tenant()->id]);
                AuditLog::create([
                    'entity_type' => 'Message',
                    'entity_id' => $message->id,
                    'action' => 'create',
                    'correlation_id' => $this->correlationId,
                    'user_id' => auth()->id(),
                ]);
                return $message;
            });
        } catch (Throwable $e) {
            Log::error('CommunicationService.sendMessage failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function markAsRead(Message $message): Message
    {
        return DB::transaction(function () use ($message) {
            $message->update(['read_at' => now()]);
            return $message;
        });
    }

    public function deleteMessage(Message $message): bool
    {
        return DB::transaction(function () use ($message) {
            $message->delete();
            return true;
        });
    }
}
