<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum ExoticProcedureType: string
{
    // Birds
    case CLAW_TRIM = 'claw_trim';
    case BEAK_TRIM = 'beak_trim';
    case WING_CLIP = 'wing_clip';
    case FEATHER_CARE = 'feather_care';

    // Reptiles
    case SCALE_CARE = 'scale_care';
    case SHELL_CLEANING = 'shell_cleaning';
    case SHEDDING_ASSIST = 'shedding_assist';

    // Small Mammals
    case EAR_CLEAN = 'ear_clean';
    case ANAL_GLAND = 'anal_gland';
    case MAT_REMOVAL = 'mat_removal';
    case BATH = 'bath';

    // Large Mammals
    case FULL_GROOM = 'full_groom';
    case DESHEDDING = 'deshedding';
    case SANITARY_TRIM = 'sanitary_trim';
    case SHOW_GROOM = 'show_groom';

    // General
    case NAIL_TRIM = 'nail_trim';
    case BASIC_CARE = 'basic_care';

    public function getLabel(): string
    {
        return match ($this) {
            self::CLAW_TRIM => 'Стрижка когтей',
            self::BEAK_TRIM => 'Обработка клюва',
            self::WING_CLIP => 'Подрезка крыльев',
            self::FEATHER_CARE => 'Уход за перьями',
            self::SCALE_CARE => 'Уход за чешуей',
            self::SHELL_CLEANING => 'Чистка панциря',
            self::SHEDDING_ASSIST => 'Помощь при линьке',
            self::EAR_CLEAN => 'Чистка ушей',
            self::ANAL_GLAND => 'Анальные железы',
            self::MAT_REMOVAL => 'Удаление колтунов',
            self::BATH => 'Купание',
            self::FULL_GROOM => 'Полный груминг',
            self::DESHEDDING => 'Удаление подшерстка',
            self::SANITARY_TRIM => 'Санитарная стрижка',
            self::SHOW_GROOM => 'Шоу-груминг',
            self::NAIL_TRIM => 'Стрижка когтей',
            self::BASIC_CARE => 'Базовый уход',
        };
    }

    public function getCategory(): ExoticCategory
    {
        return match ($this) {
            self::CLAW_TRIM,
            self::BEAK_TRIM,
            self::WING_CLIP,
            self::FEATHER_CARE => ExoticCategory::BIRDS,

            self::SCALE_CARE,
            self::SHELL_CLEANING,
            self::SHEDDING_ASSIST => ExoticCategory::REPTILES,

            self::EAR_CLEAN,
            self::ANAL_GLAND,
            self::MAT_REMOVAL,
            self::BATH => ExoticCategory::SMALL_MAMMALS,

            self::FULL_GROOM,
            self::DESHEDDING,
            self::SANITARY_TRIM,
            self::SHOW_GROOM => ExoticCategory::LARGE_MAMMALS,

            self::NAIL_TRIM,
            self::BASIC_CARE => ExoticCategory::SMALL_MAMMALS, // Default
        };
    }

    public function getRiskLevel(): string
    {
        return match ($this) {
            self::BEAK_TRIM,
            self::WING_CLIP,
            self::ANAL_GLAND,
            self::MAT_REMOVAL => 'high',
            self::CLAW_TRIM,
            self::FEATHER_CARE,
            self::SCALE_CARE,
            self::SHELL_CLEANING,
            self::SHEDDING_ASSIST,
            self::FULL_GROOM,
            self::SHOW_GROOM => 'medium',
            self::EAR_CLEAN,
            self::BATH,
            self::DESHEDDING,
            self::SANITARY_TRIM,
            self::NAIL_TRIM,
            self::BASIC_CARE => 'low',
        };
    }

    public function requiresVeterinarySupervision(): bool
    {
        return in_array($this, [
            self::BEAK_TRIM,
            self::WING_CLIP,
            self::ANAL_GLAND,
        ]);
    }
}
