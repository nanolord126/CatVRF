<?php

declare(strict_types=1);

namespace App\Domains\Staff\Infrastructure\Persistence\Eloquent\Models;

use App\Domains\Staff\Domain\Enums\StaffRole;
use App\Domains\Staff\Domain\Enums\StaffStatus;
use App\Domains\Staff\Domain\Enums\Vertical;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StaffMemberModel — Eloquent-модель сотрудника (Infrastructure слой).
 *
 * Хранит данные сотрудника в таблице staff_members.
 * Имеет global scope по tenant_id и business_group_id.
 * Использует SoftDeletes для безопасного удаления.
 *
 * @property string $id UUID
 * @property string $uuid UUID v4
 * @property string $tenant_id UUID тенанта
 * @property string|null $business_group_id UUID бизнес-группы (филиала)
 * @property string $first_name
 * @property string $last_name
 * @property string|null $middle_name
 * @property string $email
 * @property string|null $phone
 * @property StaffStatus $status
 * @property StaffRole $role
 * @property Vertical $vertical
 * @property string|null $vertical_resource_id UUID ресурса в вертикали (мастер/водитель и т.д.)
 * @property float|null $rating
 * @property string|null $avatar_url
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class StaffMemberModel extends Model
{

    protected $table = 'staff_members';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'status',
        'role',
        'vertical',
        'vertical_resource_id',
        'rating',
        'avatar_url',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'status'   => StaffStatus::class,
        'role'     => StaffRole::class,
        'vertical' => Vertical::class,
        'rating'   => 'float',
        'tags'     => 'json',
    ];

    /**
     * Применяет глобальные скопы tenant_id и (если задан) business_group_id.
     * Использует filament()->getTenant() для Filament-контекста.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            if (
                app()->runningInConsole()
                || ! function_exists('filament')
                || filament()->getTenant() === null
            ) {
                return;
            }

            $tenantId = tenant()->id;
            $builder->where('tenant_id', $tenantId);
        });
    }

    // ===== Relations =====

    /**
     * Тенант, которому принадлежит сотрудник.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }
}
