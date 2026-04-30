<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum ConditionType: string
{
    case CHRONIC_DISEASE = 'chronic_disease';
    case ALLERGY_MEDICATION = 'allergy_medication';
    case ALLERGY_FOOD = 'allergy_food';
    case ALLERGY_ENVIRONMENTAL = 'allergy_environmental';
    case ANESTHESIA_INTOLERANCE = 'anesthesia_intolerance';
    case BEHAVIORAL_ISSUE = 'behavioral_issue';

    public function getLabel(): string
    {
        return match ($this) {
            self::CHRONIC_DISEASE => 'Хроническое заболевание',
            self::ALLERGY_MEDICATION => 'Аллергия на препараты',
            self::ALLERGY_FOOD => 'Пищевая аллергия',
            self::ALLERGY_ENVIRONMENTAL => 'Экзогенная аллергия',
            self::ANESTHESIA_INTOLERANCE => 'Непереносимость анестезии',
            self::BEHAVIORAL_ISSUE => 'Поведенческая особенность',
        };
    }

    public function isAllergy(): bool
    {
        return in_array($this, [
            self::ALLERGY_MEDICATION,
            self::ALLERGY_FOOD,
            self::ALLERGY_ENVIRONMENTAL,
        ]);
    }

    public function isCritical(): bool
    {
        return $this === self::ANESTHESIA_INTOLERANCE;
    }
}
