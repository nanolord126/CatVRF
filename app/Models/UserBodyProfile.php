<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class UserBodyProfile extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'user_body_profiles';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'gender',
        'height_cm',
        'weight_kg',
        'body_type',
        'shoulder_width_cm',
        'chest_circumference_cm',
        'waist_circumference_cm',
        'hip_circumference_cm',
        'inseam_cm',
        'arm_length_cm',
        'neck_circumference_cm',
        'foot_length_cm',
        'foot_width_cm',
        'calf_circumference_cm',
        'thigh_circumference_cm',
        'bust_circumference_cm',
        'underbust_circumference_cm',
        'skin_tone',
        'hair_color',
        'eye_color',
        'face_shape',
        'body_fat_percentage',
        'muscle_mass_percentage',
        'activity_level',
        'preferred_fit',
        'preferred_size_system',
        'profile_photo_url',
        'profile_photo_processed_url',
        'profile_photo_3d_avatar_url',
        'photo_consent',
        'photo_consent_date',
        'measurement_method',
        'measurement_date',
        'is_verified',
        'verified_at',
        'verified_by',
        'accuracy_score',
        'last_updated_at',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'height_cm' => 'float',
        'weight_kg' => 'float',
        'shoulder_width_cm' => 'float',
        'chest_circumference_cm' => 'float',
        'waist_circumference_cm' => 'float',
        'hip_circumference_cm' => 'float',
        'inseam_cm' => 'float',
        'arm_length_cm' => 'float',
        'neck_circumference_cm' => 'float',
        'foot_length_cm' => 'float',
        'foot_width_cm' => 'float',
        'calf_circumference_cm' => 'float',
        'thigh_circumference_cm' => 'float',
        'bust_circumference_cm' => 'float',
        'underbust_circumference_cm' => 'float',
        'body_fat_percentage' => 'float',
        'muscle_mass_percentage' => 'float',
        'photo_consent' => 'boolean',
        'photo_consent_date' => 'datetime',
        'measurement_date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'accuracy_score' => 'float',
        'last_updated_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'correlation_id',
        'metadata',
    ];

    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_NON_BINARY = 'non_binary';
    public const GENDER_OTHER = 'other';

    public const BODY_TYPE_SLIM = 'slim';
    public const BODY_TYPE_REGULAR = 'regular';
    public const BODY_TYPE_ATHLETIC = 'athletic';
    public const BODY_TYPE_PLUS_SIZE = 'plus_size';
    public const BODY_TYPE_PETITE = 'petite';
    public const BODY_TYPE_HOURGLASS = 'hourglass';
    public const BODY_TYPE_PEAR = 'pear';
    public const BODY_TYPE_APPLE = 'apple';
    public const BODY_TYPE_RECTANGLE = 'rectangle';

    public const SKIN_TONE_VERY_FAIR = 'very_fair';
    public const SKIN_TONE_FAIR = 'fair';
    public const SKIN_TONE_MEDIUM = 'medium';
    public const SKIN_TONE_OLIVE = 'olive';
    public const SKIN_TONE_TAN = 'tan';
    public const SKIN_TONE_BROWN = 'brown';
    public const SKIN_TONE_DARK = 'dark';

    public const MEASUREMENT_METHOD_MANUAL = 'manual';
    public const MEASUREMENT_METHOD_PHOTO_SCAN = 'photo_scan';
    public const MEASUREMENT_METHOD_3D_SCAN = '3d_scan';
    public const MEASUREMENT_METHOD_AI_ESTIMATION = 'ai_estimation';

    public const PREFERRED_FIT_SLIM = 'slim';
    public const PREFERRED_FIT_REGULAR = 'regular';
    public const PREFERRED_FIT_RELAXED = 'relaxed';
    public const PREFERRED_FIT_LOOSE = 'loose';

    public const SIZE_SYSTEM_EU = 'eu';
    public const SIZE_SYSTEM_US = 'us';
    public const SIZE_SYSTEM_UK = 'uk';
    public const SIZE_SYSTEM_JP = 'jp';
    public const SIZE_SYSTEM_CN = 'cn';

    public function scopeByGender(Builder $query, string $gender): Builder
    {
        return $query->where('gender', $gender);
    }

    public function scopeByBodyType(Builder $query, string $bodyType): Builder
    {
        return $query->where('body_type', $bodyType);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeWithPhotoConsent(Builder $query): Builder
    {
        return $query->where('photo_consent', true);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('last_updated_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tryOnResults(): MorphMany
    {
        return $this->morphMany(VirtualTryOnResult::class, 'user_profile');
    }

    public function getBMIAttribute(): ?float
    {
        if (!$this->height_cm || !$this->weight_kg) {
            return null;
        }

        $heightM = $this->height_cm / 100;
        return round($this->weight_kg / ($heightM * $heightM), 2);
    }

    public function getBMICategoryAttribute(): ?string
    {
        $bmi = $this->bmi;

        if ($bmi === null) {
            return null;
        }

        return match(true) {
            $bmi < 18.5 => 'underweight',
            $bmi < 25 => 'normal',
            $bmi < 30 => 'overweight',
            default => 'obese',
        };
    }

    public function getWaistToHipRatioAttribute(): ?float
    {
        if (!$this->waist_circumference_cm || !$this->hip_circumference_cm) {
            return null;
        }

        return round($this->waist_circumference_cm / $this->hip_circumference_cm, 2);
    }

    public function getShoulderToHipRatioAttribute(): ?float
    {
        if (!$this->shoulder_width_cm || !$this->hip_circumference_cm) {
            return null;
        }

        return round($this->shoulder_width_cm / $this->hip_circumference_cm, 2);
    }

    public function getBodyShapeCalculatedAttribute(): ?string
    {
        if (!$this->bust_circumference_cm || !$this->waist_circumference_cm || !$this->hip_circumference_cm) {
            return $this->body_type;
        }

        $bust = $this->bust_circumference_cm;
        $waist = $this->waist_circumference_cm;
        $hip = $this->hip_circumference_cm;

        $difference = $bust - $hip;
        $waistDifference = $bust - $waist;

        if (abs($difference) <= 2 && abs($waistDifference) >= 9) {
            return self::BODY_TYPE_HOURGLASS;
        } elseif ($hip > bust && hip > waist + 5) {
            return self::BODY_TYPE_PEAR;
        } elseif ($bust > hip && bust > waist + 5) {
            return self::BODY_TYPE_APPLE;
        } elseif (abs($bust - $hip) <= 3 && abs($waist - $bust) <= 5) {
            return self::BODY_TYPE_RECTANGLE;
        }

        return $this->body_type;
    }

    public function getIdealWeightAttribute(): ?float
    {
        if (!$this->height_cm) {
            return null;
        }

        $heightM = $this->height_cm / 100;

        return match($this->gender) {
            self::GENDER_MALE => round(22 * ($heightM * $heightM), 1),
            self::GENDER_FEMALE => round(21 * ($heightM * $heightM), 1),
            default => round(21.5 * ($heightM * $heightM), 1),
        };
    }

    public function getRecommendedShoeSizeEUAttribute(): ?string
    {
        if (!$this->foot_length_cm) {
            return null;
        }

        $footLength = $this->foot_length_cm;

        return match(true) {
            $footLength < 23 => '36-37',
            $footLength < 24 => '37-38',
            $footLength < 25 => '38-39',
            $footLength < 26 => '39-40',
            $footLength < 27 => '40-41',
            $footLength < 28 => '41-42',
            $footLength < 29 => '42-43',
            $footLength < 30 => '43-44',
            $footLength < 31 => '44-45',
            default => '45-46',
        };
    }

    public function getRecommendedClothingSizeAttribute(): ?string
    {
        if (!$this->chest_circumference_cm || !$this->waist_circumference_cm) {
            return null;
        }

        $chest = $this->chest_circumference_cm;
        $waist = $this->waist_circumference_cm;

        if ($this->gender === self::GENDER_FEMALE) {
            return match(true) {
                $chest < 84 => 'XS',
                $chest < 89 => 'S',
                $chest < 94 => 'M',
                $chest < 99 => 'L',
                $chest < 104 => 'XL',
                $chest < 112 => '2XL',
                default => '3XL',
            };
        } else {
            return match(true) {
                $chest < 92 => 'XS',
                $chest < 100 => 'S',
                $chest < 108 => 'M',
                $chest < 116 => 'L',
                $chest < 124 => 'XL',
                $chest < 134 => '2XL',
                default => '3XL',
            };
        }
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_processed_url ?? $this->profile_photo_url;
    }

    public function getHasCompleteProfileAttribute(): bool
    {
        $required = [
            'height_cm',
            'weight_kg',
            'body_type',
            'gender',
        ];

        foreach ($required as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }

    public function getProfileCompletenessAttribute(): float
    {
        $fields = [
            'gender', 'height_cm', 'weight_kg', 'body_type',
            'shoulder_width_cm', 'chest_circumference_cm', 'waist_circumference_cm',
            'hip_circumference_cm', 'foot_length_cm', 'foot_width_cm',
            'skin_tone', 'preferred_fit', 'preferred_size_system',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $filled++;
            }
        }

        return round(($filled / count($fields)) * 100, 1);
    }

    public function grantPhotoConsent(): void
    {
        $this->update([
            'photo_consent' => true,
            'photo_consent_date' => now(),
        ]);
        $this->clearCache();
    }

    public function revokePhotoConsent(): void
    {
        $this->update([
            'photo_consent' => false,
            'photo_consent_date' => null,
        ]);

        $this->deleteProfilePhotos();
        $this->clearCache();
    }

    public function deleteProfilePhotos(): void
    {
        try {
            if ($this->profile_photo_url) {
                Storage::disk('cdn')->delete($this->profile_photo_url);
            }
            if ($this->profile_photo_processed_url) {
                Storage::disk('cdn')->delete($this->profile_photo_processed_url);
            }
            if ($this->profile_photo_3d_avatar_url) {
                Storage::disk('cdn')->delete($this->profile_photo_3d_avatar_url);
            }

            $this->update([
                'profile_photo_url' => null,
                'profile_photo_processed_url' => null,
                'profile_photo_3d_avatar_url' => null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete profile photos', [
                'profile_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function verify(int $verifierId): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $verifierId,
        ]);
        $this->clearCache();
    }

    public function updateMeasurements(array $measurements): void
    {
        $this->update(array_merge($measurements, [
            'last_updated_at' => now(),
        ]));
        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::tags(['user_body_profiles', "user_body_profile:{$this->id}"])->flush();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = tenant()->id ?? auth()->user()?->tenant_id;
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->last_updated_at)) {
                $model->last_updated_at = now();
            }
        });

        static::updating(function ($model) {
            $model->last_updated_at = now();
            if ($model->isDirty(['height_cm', 'weight_kg', 'body_type', 'measurements'])) {
                $model->clearCache();
            }
        });

        static::deleted(function ($model) {
            $model->deleteProfilePhotos();
            $model->clearCache();
        });
    }
}
