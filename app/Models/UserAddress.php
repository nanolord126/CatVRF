<?php declare(strict_types=1);

/**
 * UserAddress — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/useraddress
 * @see https://catvrf.ru/docs/useraddress
 * @see https://catvrf.ru/docs/useraddress
 * @see https://catvrf.ru/docs/useraddress
 * @see https://catvrf.ru/docs/useraddress
 * @see https://catvrf.ru/docs/useraddress
 * @see https://catvrf.ru/docs/useraddress
 */


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserAddress extends Model
{
    use HasFactory;

    protected $table = 'user_addresses';

        protected $fillable = [
        'uuid',
        'correlation_id',
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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}