<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BloggerVerificationDocument extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'blogger_verification_documents';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'blogger_id',
            'document_type',
            'file_path',
            'verification_status',
            'verification_note',
            'verified_by',
            'verified_at',
            'correlation_id',
        ];

        protected $casts = [
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = ['correlation_id'];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('blogger_verification_documents.tenant_id', tenant()->id);
            });
        }

        public function blogger(): BelongsTo
        {
            return $this->belongsTo(BloggerProfile::class, 'blogger_id');
        }

        public function isApproved(): bool
        {
            return $this->verification_status === 'approved';
        }

        public function isPending(): bool
        {
            return $this->verification_status === 'pending';
        }

        public function isRejected(): bool
        {
            return $this->verification_status === 'rejected';
        }
}
