<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ComplianceIntegration extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $table = 'compliance_integrations';

        protected $fillable = [
            'tenant_id',
            'type',
            'inn',
            'api_token_encrypted',
            'status',
            'last_checked_at',
            'error_message',
            'correlation_id',
        ];

        protected $casts = [
            'last_checked_at' => 'datetime',
        ];

        /**
         * Booted method for global tenant scoping.
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant('id')) {
                    $builder->where('tenant_id', tenant('id'));
                }
            });
        }

        /**
         * Mutator to encrypt the API Token.
         */
        public function setApiTokenAttribute(string $value): void
        {
            $this->attributes['api_token_encrypted'] = $this->crypt->encryptString($value);
        }

        /**
         * Accessor to decrypt the API Token.
         */
        public function getApiTokenAttribute(): ?string
        {
            if (empty($this->attributes['api_token_encrypted'])) {
                return null;
            }

            try {
                return $this->crypt->decryptString($this->attributes['api_token_encrypted']);
            } catch (\Exception $e) {
                return null;
            }
        }
}
