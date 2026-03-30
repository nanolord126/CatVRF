<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionSize extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'fashion_sizes';

        protected $fillable = [
            'fashion_product_id',
            'size_type',
            'size_value',
            'stock',
            'measurements',
            'correlation_id',
        ];

        protected $casts = [
            'measurements' => 'json',
            'stock' => 'integer',
        ];

        public function product(): BelongsTo
        {
            return $this->belongsTo(FashionProduct::class, 'fashion_product_id');
        }
}
