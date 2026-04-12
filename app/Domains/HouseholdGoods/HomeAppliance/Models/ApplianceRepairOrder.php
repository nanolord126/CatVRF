<?php declare(strict_types=1);

namespace App\Domains\HouseholdGoods\HomeAppliance\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApplianceRepairOrder extends Model
{

    use HasFactory;


    use HasFactory, SoftDeletes;

        protected $table = 'appliance_repair_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'client_id',
            'master_id',
            'appliance_type',
            'brand_name',
            'model_number',
            'issue_description',
            'ai_estimate',
            'status',
            'is_b2b',
            'labor_cost_kopecks',
            'parts_cost_kopecks',
            'total_cost_kopecks',
            'visit_scheduled_at',
            'repair_started_at',
            'completed_at',
            'warranty_expires_at',
            'address_json',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'ai_estimate' => 'json',
            'address_json' => 'json',
            'tags' => 'json',
            'visit_scheduled_at' => 'datetime',
            'repair_started_at' => 'datetime',
            'completed_at' => 'datetime',
            'warranty_expires_at' => 'datetime',
            'is_b2b' => 'boolean'
        ];

        /**
         * Booted method — Канон 2026.
         * Автоматическая генерация UUID и tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (ApplianceRepairOrder $model): void {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) tenant()->id;
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function ($builder): void {
                $builder->where('tenant_id', tenant()->id);
            });
        }

        // --- Отношения ---

        public function parts()
        {
            return $this->belongsToMany(AppliancePart::class, 'appliance_repair_parts', 'repair_order_id', 'part_id')
                        ->withPivot(['quantity', 'price_at_moment_kopecks'])
                        ->withTimestamps();
        }
}
