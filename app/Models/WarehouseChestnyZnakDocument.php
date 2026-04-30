<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

/**
 * Warehouse Chestny ZNAK Document Model
 *
 * Stores Chestny ZNAK (Честный ЗНАК) documents per ФЗ-61 (pharmaceutical tracking).
 * Tracks document lifecycle from creation to signing and processing.
 *
 * @property int $id
 * @property string $document_id
 * @property string $document_number
 * @property \Illuminate\Support\Carbon $document_date
 * @property string $type
 * @property string $product_group
 * @property string $status
 * @property string|null $status_message
 * @property string|null $rejection_reason
 * @property string|null $signature
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property \Illuminate\Support\Carbon|null $synced_at
 * @property int $tenant_id
 * @property string|null $warehouse_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class WarehouseChestnyZnakDocument extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'chestny_znak_documents';

    protected $fillable = [
        'document_id',
        'document_number',
        'document_date',
        'type',
        'product_group',
        'status',
        'status_message',
        'rejection_reason',
        'signature',
        'sent_at',
        'processed_at',
        'signed_at',
        'synced_at',
        'tenant_id',
        'warehouse_id',
    ];

    protected $casts = [
        'document_date' => 'date',
        'sent_at' => 'datetime',
        'processed_at' => 'datetime',
        'signed_at' => 'datetime',
        'synced_at' => 'datetime',
        'signature' => 'encrypted',
    ];

    /**
     * Warehouse this document belongs to
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Tenant this document belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope for pending documents
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing documents
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for processed documents
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope for signed documents
     */
    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    /**
     * Scope for rejected documents
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for pharmaceutical products
     */
    public function scopePharma($query)
    {
        return $query->where('product_group', 'pharma');
    }

    /**
     * Scope for specific document type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for introduce goods documents
     */
    public function scopeIntroduceGoods($query)
    {
        return $query->where('type', 'LP_INTRODUCE_GOODS');
    }

    /**
     * Scope for ship goods documents
     */
    public function scopeShipGoods($query)
    {
        return $query->where('type', 'LP_SHIP_GOODS');
    }

    /**
     * Check if document is signed
     */
    public function isSigned(): bool
    {
        return $this->status === 'signed' && $this->signed_at !== null;
    }

    /**
     * Check if document is processed
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, ['processed', 'signed']);
    }

    /**
     * Check if document is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Mark as sent to Chestny ZNAK
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'processing',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed(string $message = null): bool
    {
        return $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'status_message' => $message,
        ]);
    }

    /**
     * Mark as signed
     */
    public function markAsSigned(string $signature): bool
    {
        return $this->update([
            'status' => 'signed',
            'signed_at' => now(),
            'signature' => $signature,
        ]);
    }

    /**
     * Mark as rejected
     */
    public function markAsRejected(string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Update sync timestamp
     */
    public function updateSyncTimestamp(): bool
    {
        return $this->update(['synced_at' => now()]);
    }
}
