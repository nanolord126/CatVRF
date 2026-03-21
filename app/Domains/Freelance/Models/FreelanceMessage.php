<?php declare(strict_types=1);

namespace App\Domains\Freelance\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FreelanceMessage extends BaseModel
{
    use SoftDeletes;

    protected $table = 'freelance_messages';

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'from_user_id',
        'to_user_id',
        'message',
        'attachments',
        'is_read',
        'read_at',
        'correlation_id',
    ];

    protected $casts = [
        'attachments' => 'json',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(FreelanceContract::class);
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($tenantId = tenant()?->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }
}
