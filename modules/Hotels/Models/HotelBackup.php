<?php declare(strict_types=1);

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Hotel extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $fillable = ['name', 'stars', 'address', 'latitude', 'longitude'];
}
