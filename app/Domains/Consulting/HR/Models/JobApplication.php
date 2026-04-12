<?php declare(strict_types=1);

namespace App\Domains\Consulting\HR\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
final class JobApplication extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'hr_applications';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'vacancy_id',
            'user_id',      // Кандидат
            'resume_url',
            'cover_letter',
            'status',       // pending, review, interview, rejected, hired
            'interview_at', // nullable datetime
            'notes',        // HR notes (internal)
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'interview_at' => 'datetime',
            'tags' => 'json',
        ];

    

        public function vacancy(): BelongsTo
        {
            return $this->belongsTo(JobVacancy::class, 'vacancy_id');
        }

        public function candidate(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }
}
