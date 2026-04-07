<?php declare(strict_types=1);

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HotelBackup extends Model
{
    use HasFactory;

    protected $table = 'hotels';

    protected $fillable = [
        'name',
        'stars',
        'address',
        'latitude',
        'longitude',
    ];
}
