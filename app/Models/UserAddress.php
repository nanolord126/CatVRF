<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserAddress extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'user_addresses';

        protected $fillable = [
            'user_id',
            'type',  // home, work, other
            'address',
            'lat',
            'lon',
            'is_default',
            'usage_count',
        ];

        protected $casts = [
            'lat' => 'float',
            'lon' => 'float',
            'is_default' => 'boolean',
        ];
}
