<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class BrowSpecializationModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'brow_specializations';

    protected $fillable = [
        'master_id',
        'specialization',
        'level',
        'notes',
    ];

    protected $casts = [
        'level' => 'string',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterModel::class, 'master_id');
    }
}
