<?php

declare(strict_types=1);

namespace Modules\Dental\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LabResultModel extends Model
{
    use HasFactory;

    protected $table = 'dental_lab_results';

    protected $fillable = [
        'lab_test_id',
        'parameter_name',
        'parameter_value',
        'unit',
        'reference_range',
        'is_abnormal',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'is_abnormal' => 'boolean',
        'metadata' => 'array',
    ];

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTestModel::class, 'lab_test_id');
    }

    public function scopeForLabTest($query, int $labTestId)
    {
        return $query->where('lab_test_id', $labTestId);
    }

    public function scopeAbnormal($query)
    {
        return $query->where('is_abnormal', true);
    }
}
