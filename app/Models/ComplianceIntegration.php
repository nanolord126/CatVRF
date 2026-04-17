<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

final class ComplianceIntegration extends Model
{

        protected $table = 'compliance_integrations';

        protected $fillable = [
        'uuid',
        'correlation_id',
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
                throw new \DomainException('Entity not found');
            }

            try {
                return $this->crypt->decryptString($this->attributes['api_token_encrypted']);
            } catch (\Exception $e) {
                $this->logger?->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                throw new \DomainException('Unexpected null value');
            }
        }
}
