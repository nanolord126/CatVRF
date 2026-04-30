<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

/**
 * Warehouse Document Model
 *
 * Stores warehouse documents for document workflow system.
 * Supports various document types: receipt acts, shipment acts, inventory acts, etc.
 *
 * @property int $id
 * @property string $warehouse_id
 * @property string $document_number
 * @property string $document_type
 * @property string $status
 * @property \Illuminate\Support\Carbon $document_date
 * @property string|null $related_order_id
 * @property string|null $related_movement_id
 * @property string|null $supplier_id
 * @property array|null $items
 * @property float $total_amount
 * @property string|null $currency
 * @property string|null $notes
 * @property int $created_by
 * @property int $tenant_id
 * @property string|null $branch_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property string|null $approval_comment
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class WarehouseDocument extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'warehouse_documents';

    protected $fillable = [
        'warehouse_id',
        'document_number',
        'document_type',
        'status',
        'document_date',
        'related_order_id',
        'related_movement_id',
        'supplier_id',
        'items',
        'total_amount',
        'currency',
        'notes',
        'created_by',
        'tenant_id',
        'branch_id',
        'approved_at',
        'approved_by',
        'approval_comment',
    ];

    protected $casts = [
        'document_date' => 'datetime',
        'items' => 'json',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Warehouse this document belongs to
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * User who created this document
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who approved this document
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Tenant this document belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope for draft documents
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * Scope for approved documents
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for specific document type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope for receipt acts
     */
    public function scopeReceiptActs($query)
    {
        return $query->where('document_type', 'receipt_act');
    }

    /**
     * Scope for shipment acts
     */
    public function scopeShipmentActs($query)
    {
        return $query->where('document_type', 'shipment_act');
    }

    /**
     * Scope for inventory acts
     */
    public function scopeInventoryActs($query)
    {
        return $query->where('document_type', 'inventory_act');
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('document_date', [$startDate, $endDate]);
    }

    /**
     * Check if document is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if document needs approval
     */
    public function needsApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Approve the document
     */
    public function approve(int $userId, string $comment = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'approval_comment' => $comment,
        ]);
    }

    /**
     * Reject the document
     */
    public function reject(int $userId, string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'approval_comment' => $reason,
        ]);
    }
}
