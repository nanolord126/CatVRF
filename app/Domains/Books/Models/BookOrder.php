<?php declare(strict_types=1);

namespace App\Domains\Books\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class BookOrder extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'book_orders';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'book_id', 'user_id', 'quantity', 'total_price', 'status', 'meta'
    ];
    protected $casts = [
        'quantity' => 'int',
        'total_price' => 'int',
        'meta' => 'json',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
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
