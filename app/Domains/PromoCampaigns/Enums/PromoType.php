<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\PromoCampaigns\Enums;

/**
 * Исключительно строгий перечислитель (Enum) для бескомпромиссной типизации видов промо-кампаний.
 *
 * Категорически гарантирует точное определение механики расчета скидки, подарка или бонуса,
 * предотвращая любые несанкционированные подмены типов акций на уровне базы данных и бизнес-логики.
 */
enum PromoType: string
{
    /** Безусловная процентная скидка (от 5 до 50%). */
    case DISCOUNT_PERCENT = 'discount_percent';

    /** Исключительно фиксированная скидка в абсолютной величине (в копейках). */
    case FIXED_AMOUNT = 'fixed_amount';

    /** Категорический набор товаров, продающийся как единое целое по сниженной цене (бандл). */
    case BUNDLE = 'bundle';

    /** Строгая маркетинговая механика "Купи X, получи Y" (например, 2+1 или 1+1). */
    case BUY_X_GET_Y = 'buy_x_get_y';

    /** Исключительные подарочные сертификаты, дающие, например, +3% к номиналу при зачислении на баланс. */
    case GIFT_CARD = 'gift_card';

    /** Безусловный реферальный бонус (например, 1000 руб за приглашенного клиента после совершения им минимальной траты). */
    case REFERRAL_BONUS = 'referral_bonus';

    /** Категорический бонус за достижение заданного оборота бизнесом (turnover bonus). */
    case TURNOVER_BONUS = 'turnover_bonus';
}
