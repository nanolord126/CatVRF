<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use App\Models\User;

final class B2BDeal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'business_group_id', 'vertical_id', 'lead_id',
        'pipeline_id', 'stage_id', 'company_name', 'contact_person',
        'contact_email', 'contact_phone', 'title', 'description',
        'value', 'currency', 'status', 'priority', 'contract_type',
        'contract_start_date', 'contract_end_date', 'expected_close_date',
        'actual_close_date', 'assigned_to_id', 'assigned_team_id',
        'warehouse_id', 'probability', 'won_reason', 'lost_reason',
        'metadata', 'correlation_id', 'uuid',
    ];

    protected $casts = [
        'value' => 'integer', 'expected_close_date' => 'datetime',
        'actual_close_date' => 'datetime', 'contract_start_date' => 'datetime',
        'contract_end_date' => 'datetime', 'probability' => 'integer',
        'priority' => 'integer', 'metadata' => 'json',
        'status' => \Modules\CatCRM\Domain\Enums\DealStatus::class,
    ];

    protected $table = 'crm_b2b_deals';

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function vertical(): BelongsTo { return $this->belongsTo(\Modules\CatCRM\Domain\Entities\Vertical::class); }
    public function lead(): BelongsTo { return $this->belongsTo(B2BLead::class); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(\Modules\Supermarket\Domain\Entities\Warehouse::class); }
    public function contacts(): HasMany { return $this->hasMany(B2BContact::class, 'deal_id'); }
    public function tasks(): HasMany { return $this->hasMany(Task::class)->where('entity_type', 'b2b_deal'); }

    public function moveToStage(Stage $stage): bool
    {
        $this->stage_id = $stage->id;
        if ($stage->is_won_stage) {
            $this->status = \Modules\CatCRM\Domain\Enums\DealStatus::Won;
            $this->actual_close_date = now();
        } elseif ($stage->is_lost_stage) {
            $this->status = \Modules\CatCRM\Domain\Enums\DealStatus::Lost;
            $this->actual_close_date = now();
        }
        return $this->save();
    }

    protected static function booted(): void
    {
        parent::booted();
        static::creating(fn ($m) => $m->uuid ??= \Str::uuid());
    }
}
