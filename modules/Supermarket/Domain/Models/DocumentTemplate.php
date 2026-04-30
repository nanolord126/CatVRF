<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DocumentTemplate — Шаблон документов для B2B сертификатов
 * 
 * Позволяет поставщикам создавать шаблоны для типовых документов
 */
final class DocumentTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'document_templates';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'document_type',
        'description',
        'template_content',
        'required_fields',
        'validation_rules',
        'is_active',
        'version',
        'tenant_id',
        'created_by',
    ];

    protected $casts = [
        'uuid' => 'string',
        'required_fields' => 'array',
        'validation_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Отношения
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Проверить валидность шаблона
     */
    public function isValid(): bool
    {
        return $this->is_active;
    }

    /**
     * Получить обязательные поля для заполнения
     */
    public function getRequiredFields(): array
    {
        return $this->required_fields ?? [];
    }

    /**
     * Получить правила валидации
     */
    public function getValidationRules(): array
    {
        return $this->validation_rules ?? [];
    }

    /**
     * Активировать шаблон
     */
    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Деактивировать шаблон
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Создать новую версию шаблона
     */
    public function createNewVersion(): self
    {
        $newTemplate = $this->replicate();
        $newTemplate->version = $this->version + 1;
        $newTemplate->save();
        
        return $newTemplate;
    }
}
