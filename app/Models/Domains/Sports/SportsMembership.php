declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Models\Domains\Sports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * SportsMembership
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SportsMembership extends Model
{
    use HasFactory;

    protected $table = 'sports_memberships';

    protected $fillable = [
        'tenant_id',
        'athlete_id',
        'tier',
        'status',
        'expires_at',
        'monthly_fee',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\SportsMembershipFactory::new();
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
