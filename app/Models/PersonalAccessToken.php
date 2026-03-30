<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PersonalAccessToken extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'personal_access_tokens';

        protected $fillable = [
            'name',
            'token',
            'abilities',
            'expires_at',
        ];

        protected $casts = [
            'abilities' => 'json',
            'expires_at' => 'datetime',
        ];
}
