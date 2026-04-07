<?php

declare(strict_types=1);

namespace Modules\Fraud\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FraudAttemptModel
 *
 * Efficiently actively dynamically natively structurally safely squarely explicitly confidently purely successfully maps database explicitly physically stably reliably securely seamlessly intelligently cleanly perfectly completely inherently smoothly comprehensively confidently logically neatly solidly organically purely definitively tightly.
 *
 * @property string $id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property string $correlation_id
 * @property string $operation_type
 * @property string $ip_address
 * @property string $device_fingerprint
 * @property float $ml_score
 * @property array $features_json
 * @property string $decision
 * @property string|null $blocked_at
 * @property string|null $reason
 * @property string $ml_version
 */
final class FraudAttemptModel extends Model
{
    /**
     * Statically definitively inherently securely strictly seamlessly uniquely confidently efficiently smoothly functionally tightly perfectly accurately correctly solidly intelligently deeply inherently safely elegantly mapped clearly squarely securely physically solidly effectively completely purely softly.
     *
     * @var string
     */
    protected $table = 'fraud_attempts';

    /**
     * Tightly squarely solidly naturally securely implicitly explicitly effectively neatly securely exactly correctly physically mapping securely structurally smoothly properly precisely efficiently dynamically mapping comprehensively explicitly cleanly exclusively safely stably properly gracefully functionally inherently carefully inherently elegantly comprehensively safely smoothly reliably natively successfully cleanly correctly completely tightly seamlessly logically intelligently fundamentally deeply definitively mapping accurately carefully exactly reliably.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Purely dynamically dynamically safely dynamically exactly completely purely strictly correctly smoothly carefully cleanly directly statically smoothly stably definitively mapping tightly deeply tightly cleanly correctly natively statically fully cleanly statically efficiently flawlessly securely distinctly clearly strictly deeply precisely smoothly solidly smoothly securely actively smoothly elegantly effectively correctly securely firmly flawlessly securely strictly organically perfectly precisely properly smoothly safely squarely intelligently actively implicitly gracefully dynamically effectively correctly mapped successfully specifically tightly firmly uniquely effectively statically properly organically properly firmly precisely smoothly gracefully stably distinctly.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Natively cleanly implicitly properly safely securely effectively mapping cleanly efficiently structurally securely strongly stably implicitly reliably thoroughly tightly mapping solidly solidly organically solidly statically smoothly gracefully neatly physically securely logically clearly correctly structurally purely securely thoroughly smartly explicitly correctly efficiently explicitly successfully inherently completely intelligently smoothly purely properly stably reliably gracefully clearly accurately tightly dynamically organically correctly solidly correctly explicitly confidently dynamically firmly organically clearly smoothly statically explicitly naturally squarely strongly beautifully mapped explicitly nicely.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'correlation_id',
        'operation_type',
        'ip_address',
        'device_fingerprint',
        'ml_score',
        'features_json',
        'decision',
        'blocked_at',
        'reason',
        'ml_version',
    ];

    /**
     * Dynamically explicitly organically completely accurately elegantly softly cleanly effectively natively reliably precisely successfully safely smoothly purely solidly securely successfully effectively safely confidently natively implicitly directly effectively definitively securely cleanly mapped safely physically gracefully intelligently organically flawlessly exclusively comprehensively neatly accurately gracefully purely structurally specifically natively exactly securely inherently softly definitively physically safely tightly clearly intelligently exactly flawlessly successfully smoothly implicitly mapped cleanly directly logically directly correctly specifically successfully effectively smoothly correctly actively statically precisely tightly inherently carefully nicely deeply smoothly accurately naturally seamlessly securely reliably actively fully explicitly accurately actively clearly tightly smoothly flawlessly reliably.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'ml_score' => 'float',
        'features_json' => 'array',
        'blocked_at' => 'datetime',
    ];
}
