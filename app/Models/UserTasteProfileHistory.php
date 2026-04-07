<?php declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserTasteProfileHistory extends Model
{
    use HasFactory, TenantScoped;

        protected $table = 'user_taste_profile_history';

        public $timestamps = false;

        protected $fillable = [
        'uuid',
        'correlation_id',
            'user_id',
            'tenant_id',
            'taste_profile_id',
            'version',
            'changes',
            'trigger_reason',
            'interaction_count',
            'purchase_count',
            'correlation_id',
            'created_at',
        ];

        protected $casts = [
            'changes' => 'json',
            'created_at' => 'datetime',
        ];

        protected $hidden = ['correlation_id'];

        // ========== ОТНОШЕНИЯ ==========

        public function tasteProfile(): BelongsTo
        {
            return $this->belongsTo(UserTasteProfile::class);
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function tenant(): BelongsTo
        {
            return $this->belongsTo(Tenant::class);
        }

        // ========== GETTERS ==========

        /**
         * Какие конкретно поля изменились?
         */
        public function getChangedFields(): array
        {
            $changes = $this->changes ?? [];
            return array_keys($changes);
        }

        /**
         * Получить изменение для конкретного поля
         */
        public function getFieldChange(string $field): ?array
        {
            $changes = $this->changes ?? [];
            return $changes[$field] ?? null;
        }
}
