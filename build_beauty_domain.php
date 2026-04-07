<?php
declare(strict_types=1);

$domainPath = __DIR__ . '/app/Domains/Beauty';

$directories = [
    'Models',
    'DTOs',
    'Services',
    'Services/AI',
    'Events',
    'Listeners',
    'Jobs',
    'Http/Controllers/Api/V1',
    'Http/Requests',
    'Http/Resources',
    'Filament/Resources/SalonResource/Pages',
];

foreach ($directories as $dir) {
    if (!is_dir("$domainPath/$dir")) {
        mkdir("$domainPath/$dir", 0777, true);
    }
}

$files = [];

// 1. MODELS
$files['Models/Salon.php'] = <<<'PHP'
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class Salon
 * 
 * Represents a beauty salon belonging to a specific tenant and business group.
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string $correlation_id
 * @property string $name
 * @property string $address
 * @property float $lat
 * @property float $lon
 * @property string $status
 * @property array|null $tags
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Salon extends Model
{
    protected $table = 'beauty_salons';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'address',
        'lat',
        'lon',
        'status',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'json',
        'is_active' => 'boolean',
        'lat' => 'float',
        'lon' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 1);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    public function masters(): HasMany
    {
        return $this->hasMany(Master::class);
    }
    
    public function services(): HasMany
    {
        return $this->hasMany(BeautyService::class);
    }
}

PHP;

$files['Models/Master.php'] = <<<'PHP'
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class Master
 * 
 * Represents a beauty master (specialist) working in a salon.
 */
final class Master extends Model
{
    protected $table = 'beauty_masters';

    protected $fillable = [
        'salon_id',
        'user_id',
        'uuid',
        'correlation_id',
        'full_name',
        'specialization',
        'rating',
        'is_active',
        'tags',
    ];

    protected $casts = [
        'rating' => 'float',
        'is_active' => 'boolean',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
PHP;

foreach ($files as $path => $content) {
    file_put_contents("$domainPath/$path", $content);
}

echo "Created basic models safely.\n";
