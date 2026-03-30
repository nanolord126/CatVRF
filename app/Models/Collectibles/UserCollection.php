<?php declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserCollection extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'user_collections';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'name',
            'theme',
        ];

        protected static function booted(): void
        {
            static::creating(function (UserCollection $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }

        /**
         * Get all items in this collection.
         */
        public function items(): HasMany
        {
            return $this->hasMany(CollectibleItem::class, 'collection_id');
        }
}
