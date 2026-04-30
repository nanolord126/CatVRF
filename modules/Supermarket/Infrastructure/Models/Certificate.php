<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $table = 'supermarket_certificates';

    protected $fillable = [
        'product_id',
        'certificate_number',
        'type',
        'issued_by',
        'valid_from',
        'valid_until',
        'file_path',
        'status',
        'document_url',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'valid')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDeclaration($query)
    {
        return $query->where('type', 'declaration');
    }

    public function scopeCertificate($query)
    {
        return $query->where('type', 'certificate');
    }

    public function scopeVeterinary($query)
    {
        return $query->where('type', 'veterinary');
    }

    public function isValid(): bool
    {
        return $this->status === 'valid'
            && $this->valid_from <= now()
            && $this->valid_until >= now();
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'declaration' => 'Декларация',
            'certificate' => 'Сертификат',
            'veterinary' => 'Ветеринарный',
            default => 'Другое',
        };
    }
}
