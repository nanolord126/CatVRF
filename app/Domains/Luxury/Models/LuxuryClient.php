<?php declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LuxuryClient extends Model
{


        protected $table = 'luxury_clients';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'vip_level', // silver, gold, platinum, black
            'total_spent_kopecks',
            'last_visit_at',
            'preferences', // json: coffee, preferred_color, size, etc.
            'representative_name', // личный ассистент клиента
            'notes',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'preferences' => 'json',
            'tags' => 'json',
            'last_visit_at' => 'datetime',
        ];

        protected static function booted_disabled(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('luxury_clients.tenant_id', tenant()->id);
                }
            });
        }

        public function bookings(): HasMany
        {
            return $this->hasMany(VIPBooking::class, 'client_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(LuxuryReview::class, 'client_id');
        }
}
