<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Stage — Этап воронки CRM
 * 
 * Представляет отдельный этап в воронке продаж/услуг.
 * Сделки (Deal) перемещаются между этапами.
 */
final class Stage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pipeline_id',
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'order',
        'probability',
        'is_won_stage',
        'is_lost_stage',
        'is_final_stage',
        'auto_move_timeout_hours',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'order' => 'integer',
        'probability' => 'integer', // 0-100
        'is_won_stage' => 'boolean',
        'is_lost_stage' => 'boolean',
        'is_final_stage' => 'boolean',
        'auto_move_timeout_hours' => 'integer',
        'metadata' => 'json',
    ];

    protected $table = 'crm_stages';

    // ========================
    // RELATIONSHIPS
    // ========================

    /**
     * Воронка, к которой принадлежит этап
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    /**
     * Сделки на этом этапе
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByPipeline($query, int $pipelineId)
    {
        return $query->where('pipeline_id', $pipelineId);
    }

    public function scopeWon($query)
    {
        return $query->where('is_won_stage', true);
    }

    public function scopeLost($query)
    {
        return $query->where('is_lost_stage', true);
    }

    public function scopeFinal($query)
    {
        return $query->where('is_final_stage', true);
    }

    // ========================
    // METHODS
    // ========================

    /**
     * Проверить, является ли этап финальным (выигрыш или проигрыш)
     */
    public function isFinal(): bool
    {
        return $this->is_won_stage || $this->is_lost_stage || $this->is_final_stage;
    }

    /**
     * Получить общую стоимость сделок на этом этапе
     */
    public function getTotalValue(): int
    {
        return $this->deals()->sum('value');
    }

    /**
     * Получить количество сделок на этом этапе
     */
    public function getDealsCount(): int
    {
        return $this->deals()->count();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            $model->slug ??= \Illuminate\Support\Str::slug($model->name);
        });
    }
}
