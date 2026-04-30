<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum ExoticGroup: string
{
    case GROUP_A = 'group_a'; // High risk - requires Master Exotic Certification
    case GROUP_B = 'group_b'; // Medium risk - Advanced Exotic
    case GROUP_C = 'group_c'; // Basic level - Certified Exotic

    public function getLabel(): string
    {
        return match ($this) {
            self::GROUP_A => 'Группа A (Высокий риск)',
            self::GROUP_B => 'Группа B (Средний риск)',
            self::GROUP_C => 'Группа C (Базовый уровень)',
        };
    }

    public function getRequiredCertificationLevel(): CertificationLevel
    {
        return match ($this) {
            self::GROUP_A => CertificationLevel::MASTER,
            self::GROUP_B => CertificationLevel::ADVANCED,
            self::GROUP_C => CertificationLevel::CERTIFIED,
        };
    }

    public function getRiskLevel(): string
    {
        return match ($this) {
            self::GROUP_A => 'critical',
            self::GROUP_B => 'high',
            self::GROUP_C => 'medium',
        };
    }

    public function getSpecies(): array
    {
        return match ($this) {
            self::GROUP_A => [
                'Хорьки',
                'Кролики декоративные (особенно карликовые)',
                'Морские свинки',
                'Шиншиллы',
                'Дегу',
                'Попугаи крупные (ара, какаду, жако)',
                'Игуаны',
                'Хамелеоны',
                'Бородатые агамы',
            ],
            self::GROUP_B => [
                'Крысы',
                'Хомяки',
                'Песчанки',
                'Мелкие птицы (волнистые попугайчики, кореллы)',
                'Черепахи наземные',
                'Ежи африканские',
            ],
            self::GROUP_C => [
                'Рептилии мелкие (гекконы)',
                'Амфибии (лягушки, аксолотли)',
            ],
        };
    }
}
