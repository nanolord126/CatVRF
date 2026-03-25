declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

final /**
 * PersonalAccessToken
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PersonalAccessToken extends SanctumToken
{
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
