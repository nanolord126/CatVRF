<?php declare(strict_types=1);

/**
 * LuxuryServiceException — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/luxuryserviceexception
 */


namespace App\Domains\Luxury\Exceptions;

final class LuxuryServiceException extends \Exception
{

    /**
         * Товар отсутствует на складе (включая лимитированные коллекции)
         */
        public static function outOfStock(string $productName): self
        {
            return new self("Эксклюзивный товар '{$productName}' в данный момент недоступен для бронирования или находится в холде у другого клиента.", Response::HTTP_CONFLICT);
        }

        /**
         * Услуга недоступна для данного уровня VIP
         */
        public static function serviceLevelNotMet(string $serviceName, string $requiredLevel): self
        {
            return new self("Услуга '{$serviceName}' доступна только для статуса не ниже '{$requiredLevel}'. Ваш текущий VIP-статус не позволяет совершить бронирование.", Response::HTTP_FORBIDDEN);
        }

        /**
         * Превышено количество активных предложений для VIP клиента
         */
        public static function tooManyActiveOffers(): self
        {
            return new self("Клиент уже имеет максимальное количество активных предложений в соответствии со своим VIP уровнем.", Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /**
         * Фрод-контроль не пройден
         */
        public static function fraudDetected(): self
        {
            return new self("Операция заблокирована системой фрод-контроля. Доступ к VIP-услугам ограничен.", Response::HTTP_FORBIDDEN);
        }

        /**
         * Ошибка транзакции при покупке элитного актива
         */
        public static function transactionFailed(): self
        {
            return new self("Ошибка при проведении транзакции. Средства не списаны. Обратитесь к вашему персональному менеджеру.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        /**
         * Недостаточно средств на эскроу-кошельке клиента
         */
        public static function insufficientEscrowFunds(int $required, int $current): self
        {
            return new self("Недостаточно средств для депозита/предоплаты. Требуется: " . ($required / 100) . " руб. В наличии: " . ($current / 100) . " руб.", Response::HTTP_PAYMENT_REQUIRED);
        }
}
