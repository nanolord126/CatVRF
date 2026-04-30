<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class LashCertificationModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lash_certifications';

    protected $fillable = [
        'master_id',
        'certification_type',
        'name',
        'issuer',
        'issue_date',
        'expiry_date',
        'certificate_number',
        'document_file',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'certification_type' => 'string',
        'status' => 'string',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterModel::class, 'master_id');
    }
}
