<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum HandlingMethod: string
{
    case TOWEL = 'towel';
    case SCRUFF = 'scruff';
    case GENTLE_RESTRAINT = 'gentle_restraint';
    case TWO_PERSON = 'two_person';
    case MUZZLE = 'muzzle';
    case GENTLE_LEADER = 'gentle_leader';
    case SPECIAL_BAG = 'special_bag';
    case GLOVES = 'gloves';
    case NO_RESTRAINT = 'no_restraint';
    case CHEMICAL_SEDATION = 'chemical_sedation';

    public function getLabel(): string
    {
        return match ($this) {
            self::TOWEL => 'Фиксация в полотенце',
            self::SCRUFF => 'Скруффинг (только для опытных)',
            self::GENTLE_RESTRAINT => 'Мягкая фиксация',
            self::TWO_PERSON => 'Двухгрумерная фиксация',
            self::MUZZLE => 'Намордник',
            self::GENTLE_LEADER => 'Gentle Leader',
            self::SPECIAL_BAG => 'Специальная сумка-фиксатор',
            self::GLOVES => 'Работа в перчатках',
            self::NO_RESTRAINT => 'Без фиксации',
            self::CHEMICAL_SEDATION => 'Химическая седация (только ветеринаром)',
        };
    }

    public function getApplicableCategories(): array
    {
        return match ($this) {
            self::TOWEL => [ExoticCategory::BIRDS, ExoticCategory::SMALL_MAMMALS],
            self::SCRUFF => [ExoticCategory::SMALL_MAMMALS],
            self::GENTLE_RESTRAINT => [ExoticCategory::BIRDS, ExoticCategory::SMALL_MAMMALS, ExoticCategory::REPTILES],
            self::TWO_PERSON => [ExoticCategory::LARGE_MAMMALS],
            self::MUZZLE => [ExoticCategory::LARGE_MAMMALS],
            self::GENTLE_LEADER => [ExoticCategory::LARGE_MAMMALS],
            self::SPECIAL_BAG => [ExoticCategory::BIRDS, ExoticCategory::SMALL_MAMMALS],
            self::GLOVES => [ExoticCategory::REPTILES, ExoticCategory::SMALL_MAMMALS],
            self::NO_RESTRAINT => [ExoticCategory::REPTILES],
            self::CHEMICAL_SEDATION => [ExoticCategory::BIRDS, ExoticCategory::REPTILES, ExoticCategory::SMALL_MAMMALS, ExoticCategory::LARGE_MAMMALS],
        };
    }

    public function requiresVeterinaryApproval(): bool
    {
        return $this === self::CHEMICAL_SEDATION;
    }

    public function getRisk(): string
    {
        return match ($this) {
            self::SCRUFF,
            self::CHEMICAL_SEDATION => 'high',
            self::TWO_PERSON,
            self::MUZZLE => 'medium',
            self::TOWEL,
            self::GENTLE_RESTRAINT,
            self::GENTLE_LEADER,
            self::SPECIAL_BAG,
            self::GLOVES,
            self::NO_RESTRAINT => 'low',
        };
    }
}
