<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * ForceMajeureParty (Canon 2026)
 * Сторона, ответственная за возникновение форс-мажорной ситуации.
 */
enum ForceMajeureParty: string
{
    case CLIENT = 'client';       // Клиент (болезнь, смерть родственника и т.д.)
    case SALON = 'salon';         // Салон (отключение света, воды, болезнь мастера)
    case PLATFORM = 'platform';   // Платформа (технический сбой, ошибка биллинга)
    case EXTERNAL = 'external';   // Внешние факторы (стихийные бедствия, война, госорганы)
}
