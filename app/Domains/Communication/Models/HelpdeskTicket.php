<?php

namespace App\Domains\Communication\Models;

use App\Traits\Common\{HasEcosystemFeatures, HasEcosystemAuth};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\{Carbon, Str};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Throwable;

class HelpdeskTicket extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;
    
    protected $guarded = [];
    protected $casts = [
        'metadata' => 'array',
        'is_resolved' => 'boolean',
        'correlation_id' => 'string',
        'tenant_id' => 'integer',
        'assigned_to' => 'integer',
        'priority' => 'string',
        'resolution_notes' => 'string',
        'resolved_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    protected static function booted()
    {
        static::creating(function (self $ticket) {
            $ticket->correlation_id = $ticket->correlation_id ?? Str::uuid();
            $ticket->tenant_id = $ticket->tenant_id ?? Auth::guard('tenant')?->id();
            
            if (empty($ticket->priority)) {
                $ticket->priority = 'normal';
            }
        });

        static::created(function (self $ticket) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $ticket->id,
                    'action' => 'created',
                    'user_id' => Auth::id(),
                    'tenant_id' => $ticket->tenant_id,
                    'correlation_id' => $ticket->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'title' => $ticket->title ?? 'Untitled',
                        'category' => $ticket->category ?? 'general',
                        'priority' => $ticket->priority,
                        'status' => 'open',
                    ],
                ]);

                Log::channel('communication')->info('HelpdeskTicket created', [
                    'ticket_id' => $ticket->id,
                    'correlation_id' => $ticket->correlation_id,
                    'user_id' => Auth::id(),
                    'priority' => $ticket->priority,
                ]);
            } catch (Throwable $e) {
                Log::error('HelpdeskTicket creation audit failed', [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
            }
        });

        static::updating(function (self $ticket) {
            if ($ticket->isDirty('is_resolved') && $ticket->is_resolved) {
                $ticket->resolved_at = $ticket->resolved_at ?? Carbon::now();
            }
        });

        static::updated(function (self $ticket) {
            try {
                $changes = $ticket->getChanges();
                
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $ticket->id,
                    'action' => 'updated',
                    'user_id' => Auth::id(),
                    'tenant_id' => $ticket->tenant_id,
                    'correlation_id' => $ticket->correlation_id ?? Str::uuid(),
                    'changes' => $changes,
                    'metadata' => [
                        'status' => $ticket->is_resolved ? 'resolved' : 'open',
                        'assigned_to' => $ticket->assigned_to,
                        'priority' => $ticket->priority,
                    ],
                ]);

                Log::channel('communication')->info('HelpdeskTicket updated', [
                    'ticket_id' => $ticket->id,
                    'correlation_id' => $ticket->correlation_id,
                    'user_id' => Auth::id(),
                    'changes' => array_keys($changes),
                ]);
            } catch (Throwable $e) {
                Log::error('HelpdeskTicket update audit failed', [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
            }
        });

        static::deleted(function (self $ticket) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $ticket->id,
                    'action' => 'deleted',
                    'user_id' => Auth::id(),
                    'tenant_id' => $ticket->tenant_id,
                    'correlation_id' => $ticket->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'title' => $ticket->title,
                        'was_resolved' => $ticket->is_resolved,
                        'priority' => $ticket->priority,
                    ],
                ]);

                Log::channel('communication')->warning('HelpdeskTicket deleted', [
                    'ticket_id' => $ticket->id,
                    'correlation_id' => $ticket->correlation_id,
                    'user_id' => Auth::id(),
                ]);
            } catch (Throwable $e) {
                Log::error('HelpdeskTicket deletion audit failed', [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}

class TicketMessage extends Model
{
    use HasEcosystemFeatures;
    
    protected $guarded = [];
    protected $casts = [
        'metadata' => 'array',
        'correlation_id' => 'string',
        'tenant_id' => 'integer',
        'is_internal' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(HelpdeskTicket::class);
    }

    protected static function booted()
    {
        static::creating(function (self $message) {
            $message->correlation_id = $message->correlation_id ?? Str::uuid();
            $message->tenant_id = $message->tenant_id ?? Auth::guard('tenant')?->id();
        });

        static::created(function (self $message) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $message->id,
                    'action' => 'created',
                    'user_id' => Auth::id(),
                    'tenant_id' => $message->tenant_id,
                    'correlation_id' => $message->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'ticket_id' => $message->helpdesk_ticket_id,
                        'is_internal' => $message->is_internal ?? false,
                        'message_length' => strlen($message->message ?? ''),
                    ],
                ]);

                Log::channel('communication')->info('TicketMessage created', [
                    'message_id' => $message->id,
                    'ticket_id' => $message->helpdesk_ticket_id,
                    'correlation_id' => $message->correlation_id,
                    'user_id' => Auth::id(),
                ]);
            } catch (Throwable $e) {
                Log::error('TicketMessage creation audit failed', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
                \Sentry\captureException($e);
            }
        });

        static::deleted(function (self $message) {
            try {
                AuditLog::create([
                    'entity_type' => self::class,
                    'entity_id' => $message->id,
                    'action' => 'deleted',
                    'user_id' => Auth::id(),
                    'tenant_id' => $message->tenant_id,
                    'correlation_id' => $message->correlation_id,
                    'changes' => [],
                    'metadata' => [
                        'ticket_id' => $message->helpdesk_ticket_id,
                    ],
                ]);

                Log::channel('communication')->warning('TicketMessage deleted', [
                    'message_id' => $message->id,
                    'ticket_id' => $message->helpdesk_ticket_id,
                    'correlation_id' => $message->correlation_id,
                    'user_id' => Auth::id(),
                ]);
            } catch (Throwable $e) {
                Log::error('TicketMessage deletion audit failed', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
