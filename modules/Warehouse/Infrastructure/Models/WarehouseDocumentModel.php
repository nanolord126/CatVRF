<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Warehouse Document Model
 */
final class WarehouseDocumentModel extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_documents';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
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
        'updated_at',
    ];

    protected $casts = [
        'document_date' => 'datetime',
        'items' => 'array',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'approved_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationship to warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    /**
     * Relationship to user who created the document
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Relationship to user who approved the document
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Scope for documents pending approval
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
     * Scope for draft documents
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Check if document can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if document can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if document can be archived
     */
    public function canBeArchived(): bool
    {
        return $this->status === 'approved';
    }
}
