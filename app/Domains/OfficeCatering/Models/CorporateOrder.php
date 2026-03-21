<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class CorporateOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'corporate_orders';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'corporate_client_id', 'office_menu_id',
        'uuid', 'correlation_id', 'idempotency_key',
        'persons_count', 'total_amount', 'delivery_date', 'delivery_time',
        'delivery_address', 'status', 'payment_status',
        'is_recurring', 'recurrence', 'delivered_at', 'tags',
    ];
    protected $casts = [
        'persons_count'  => 'int',
        'total_amount'   => 'int',
        'is_recurring'   => 'boolean',
        'delivery_date'  => 'date',
        'delivered_at'   => 'datetime',
        'tags'           => 'json',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CorporateClient::class, 'corporate_client_id');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(OfficeMenu::class, 'office_menu_id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
