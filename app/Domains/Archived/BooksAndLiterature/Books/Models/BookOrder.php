<?php declare(strict_types=1);

namespace App\Domains\Archived\BooksAndLiterature\Books\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'book_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'correlation_id',
            'status',
            'total_price',
            'tags',
            'meta',
        ];

        protected $casts = [
            'tags' => 'json',
            'meta' => 'json',
            'total_price' => 'integer',
        ];
    }


    use Illuminate\Database\Eloquent\Concerns\HasUuids;


    use Illuminate\Database\Eloquent\Model;


    use Illuminate\Database\Eloquent\SoftDeletes;


    use App\Traits\TenantScoped;


    final class BookOrder extends Model


    {


        use HasUuids, SoftDeletes, TenantScoped;


        protected $table = 'book_orders';


        protected $fillable = ['uuid', 'tenant_id', 'client_id', 'correlation_id', 'status', 'total_kopecks', 'payout_kopecks', 'payment_status', 'items_json', 'tags'];


        protected $casts = ['total_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'items_json' => 'json', 'tags' => 'json'];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant', fn($q) => $q->where('book_orders.tenant_id', tenant()->id));


        }
}
