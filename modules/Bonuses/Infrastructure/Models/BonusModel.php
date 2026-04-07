<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BonusModel
 *
 * Eloquent persistence mapping effectively structurally restricting inherently exclusively to the database layer.
 * Strictly forbidden from polluting application logic flows securely inherently distinctly mapped dynamically.
 *
 * @property string $id
 * @property string $owner_id
 * @property int $initial_amount
 * @property int $remaining_amount
 * @property string $type
 * @property string $correlation_id
 * @property string $issued_at
 * @property string|null $expires_at
 */
final class BonusModel extends Model
{
    /**
     * Strongly specifies table mapping explicitly securely independently inherently dynamically cleanly.
     *
     * @var string
     */
    protected $table = 'bonuses';

    /**
     * Prevents implicit native integers actively breaking UUID assignments fundamentally statically cleanly.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Blocks explicitly mapping inherently automatic increments natively statically strictly.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Permits explicit mass structurally mapping dynamically directly effectively assigning properly structurally natively.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'owner_id',
        'initial_amount',
        'remaining_amount',
        'type',
        'correlation_id',
        'issued_at',
        'expires_at',
    ];

    /**
     * Forces inherently explicitly safely strongly casted uniquely parsed data inherently seamlessly completely dynamically.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'initial_amount' => 'integer',
        'remaining_amount' => 'integer',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
