<?php

declare(strict_types=1);

namespace App\Models\AI;

use App\Models\Concerns\HasTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AIConstruction extends Model
{
    use HasFactory;
    use HasTenant;
    use HasUuid;

    protected $table = 'ai_constructions';

    protected $fillable = [
        'uuid',
        'user_id',
        'tenant_id',
        'correlation_id',
        'constructor_type',
        'input_parameters',
        'used_taste_profile',
        'result',
        'confidence_score',
    ];

    protected $casts = [
        'input_parameters' => 'json',
        'used_taste_profile' => 'json',
        'result' => 'json',
        'confidence_score' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
