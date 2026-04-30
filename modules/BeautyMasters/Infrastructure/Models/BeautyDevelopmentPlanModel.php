<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class BeautyDevelopmentPlanModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'beauty_development_plans';

    protected $fillable = [
        'master_id',
        'required_courses',
        'progress_percent',
        'next_certification_date',
        'mentor_notes',
    ];

    protected $casts = [
        'required_courses' => 'array',
        'progress_percent' => 'integer',
        'next_certification_date' => 'date',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterModel::class, 'master_id');
    }
}
