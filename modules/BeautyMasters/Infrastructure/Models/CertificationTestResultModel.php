<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CertificationTestResultModel extends Model
{
    use HasFactory;

    protected $table = 'certification_test_results';

    protected $fillable = [
        'master_id',
        'test_name',
        'vertical',
        'theory_score',
        'practice_score',
        'total_score',
        'passed',
        'awarded_level',
        'practical_work_photos',
        'feedback',
        'completed_at',
    ];

    protected $casts = [
        'theory_score' => 'integer',
        'practice_score' => 'integer',
        'total_score' => 'integer',
        'passed' => 'boolean',
        'awarded_level' => 'string',
        'practical_work_photos' => 'array',
        'completed_at' => 'datetime',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterModel::class, 'master_id');
    }
}
