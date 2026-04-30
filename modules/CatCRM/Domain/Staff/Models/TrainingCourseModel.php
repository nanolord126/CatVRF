<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TrainingCourseModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 */
final class TrainingCourseModel extends Model
{
    use HasFactory;

    protected $table = 'staff_training_courses';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'category',
        'duration_hours',
        'difficulty_level',
        'required_skills',
        'acquired_skills',
        'points_reward',
        'external_url',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'acquired_skills' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];
}
