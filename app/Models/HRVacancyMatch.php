<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HRVacancyMatch extends Model
{
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'hr_vacancy_matches';

    protected $fillable = [
        'vacancy_id',
        'user_id',
        'match_score',
        'match_reasons',
    ];

    protected $casts = [
        'match_reasons' => 'array',
        'match_score' => 'float',
    ];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(HRJobVacancy::class, 'vacancy_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}









