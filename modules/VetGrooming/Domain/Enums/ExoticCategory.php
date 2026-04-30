<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum ExoticCategory: string
{
    case BIRDS = 'birds';
    case REPTILES = 'reptiles';
    case SMALL_MAMMALS = 'small_mammals';
    case LARGE_MAMMALS = 'large_mammals';

    public function getLabel(): string
    {
        return match ($this) {
            self::BIRDS => 'Птицы',
            self::REPTILES => 'Рептилии',
            self::SMALL_MAMMALS => 'Мелкие млекопитающие',
            self::LARGE_MAMMALS => 'Крупные млекопитающие',
        };
    }

    public function getSubcategories(): array
    {
        return match ($this) {
            self::BIRDS => [
                'large_parrots', // ара, какаду, жако
                'medium_parrots', // корелла, розелла
                'small_birds', // волнистые, неразлучники
                'canaries', // канарейки, амадины
                'poultry', // куры декоративные, голуби
            ],
            self::REPTILES => [
                'iguanas', // игуаны
                'lizards', // бородатые агамы, хамелеоны
                'geckos', // гекконы
                'turtles', // черепахи
                'snakes', // змеи (только опытные)
            ],
            self::SMALL_MAMMALS => [
                'ferrets', // хорьки
                'rabbits', // кролики
                'chinchillas', // шиншиллы
                'guinea_pigs', // морские свинки
                'degus', // дегу
                'hedgehogs', // ежи
                'rodents', // крысы, хомяки, песчанки
            ],
            self::LARGE_MAMMALS => [
                'large_dogs', // крупные собаки
                'giant_dogs', // гигантские породы
                'large_cats', // крупные кошки (мейн-куны и т.д.)
                'other_large', // мини-пиги, альпаки
            ],
        };
    }

    public function getPrimaryRisks(): array
    {
        return match ($this) {
            self::BIRDS => [
                'Стресс → самотравмирование, выщипывание перьев',
                'Повреждение маховых и рулевых перьев',
                'Аспирация пыли/пуха',
                'Перегрев / переохлаждение',
            ],
            self::REPTILES => [
                'Укусы, царапины, хвостовая автотомия',
                'Повреждение чешуи и панциря',
                'Стресс → отказ от еды, иммуносупрессия',
                'Неправильная температура и влажность',
            ],
            self::SMALL_MAMMALS => [
                'Высокая стрессочувствительность',
                'Риск травм при неправильной фиксации',
                'Специфические заболевания кожи',
                'Анатомические особенности каждого вида',
            ],
            self::LARGE_MAMMALS => [
                'Высокий риск агрессии',
                'Физическая сила и размеры животного',
                'Риск травматизма для грумера',
                'Сложность фиксации',
            ],
        };
    }
}
