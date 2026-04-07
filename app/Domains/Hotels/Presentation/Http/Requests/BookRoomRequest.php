<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Http\Requests;

use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * BookRoomRequest — Запрос на бронирование номера в отеле (B2C).
 *
 * Выполняет:
 * - Rate limiting: 20 попыток бронирования в час на пользователя
 * - Fraud check через FraudControlService (через IoC-контейнер, не статически)
 * - Полную валидацию данных с читаемыми сообщениями об ошибках
 * - Проверку максимального периода бронирования (365 ночей)
 *
 * @package App\Domains\Hotels\Presentation\Http\Requests
 */
final class BookRoomRequest extends FormRequest
{
    /**
     * Максимальное количество попыток бронирования в час.
     */
    private const RATE_LIMIT_MAX_ATTEMPTS = 20;

    /**
     * Время жизни ограничителя запросов в секундах (1 час).
     */
    private const RATE_LIMIT_DECAY_SECONDS = 3600;

    /**
     * Максимально допустимый период бронирования в ночах.
     */
    private const MAX_BOOKING_NIGHTS = 365;

    /**
     * Проверяет авторизацию, применяет rate limiting и fraud check.
     *
     * Все зависимости разрешаются через IoC-контейнер ($this->container),
     * статические фасады не используются.
     */
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $correlationId = $this->header('X-Correlation-ID') ?? Uuid::uuid4()->toString();
        $userId        = (int) $this->user()->getAuthIdentifier();
        $rateLimitKey  = sprintf('hotel_booking:%d:%s', $userId, $this->ip());

        /** @var RateLimiter $rateLimiter */
        $rateLimiter = $this->container->make(RateLimiter::class);

        if ($rateLimiter->tooManyAttempts($rateLimitKey, self::RATE_LIMIT_MAX_ATTEMPTS)) {
            /** @var LoggerInterface $auditLogger */
            $auditLogger = $this->container->make('log')->channel('audit');
            $auditLogger->warning('Hotel booking rate limit exceeded.', [
                'user_id'        => $userId,
                'ip'             => $this->ip(),
                'correlation_id' => $correlationId,
                'class'          => self::class,
            ]);

            throw new ThrottleRequestsException(
                'Превышен лимит запросов на бронирование. Попробуйте через час.'
            );
        }

        $rateLimiter->hit($rateLimitKey, self::RATE_LIMIT_DECAY_SECONDS);

        /** @var FraudControlService $fraudService */
        $fraudService = $this->container->make(FraudControlService::class);
        $fraudResult  = $fraudService->check(
            $userId,
            'hotel_booking',
            0,
            (string) $this->ip(),
            (string) $this->header('X-Device-Fingerprint', ''),
            $correlationId,
        );

        if (($fraudResult['decision'] ?? 'allow') === 'block') {
            /** @var LoggerInterface $fraudLogger */
            $fraudLogger = $this->container->make('log')->channel('fraud_alert');
            $fraudLogger->warning('Hotel booking blocked by fraud service.', [
                'user_id'        => $userId,
                'score'          => $fraudResult['score'] ?? null,
                'correlation_id' => $correlationId,
                'ip'             => $this->ip(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Правила валидации бронирования.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'room_id'          => ['required', 'uuid', 'exists:hotel_rooms,id'],
            'check_in_date'    => ['required', 'date', 'after_or_equal:today'],
            'check_out_date'   => ['required', 'date', 'after:check_in_date'],
            'guests_count'     => ['sometimes', 'nullable', 'integer', 'min:1', 'max:20'],
            'special_requests' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Дополнительная валидация после стандартных правил.
     * Проверяет максимальный период бронирования.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $checkIn  = $this->input('check_in_date');
            $checkOut = $this->input('check_out_date');

            if ($checkIn === null || $checkOut === null) {
                return;
            }

            $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));

            if ($nights > self::MAX_BOOKING_NIGHTS) {
                $v->errors()->add(
                    'check_out_date',
                    sprintf('Максимальный срок бронирования — %d ночей.', self::MAX_BOOKING_NIGHTS)
                );
            }
        });
    }

    /**
     * Человекочитаемые сообщения об ошибках валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'room_id.required'             => 'Укажите номер для бронирования.',
            'room_id.uuid'                 => 'Неверный формат идентификатора номера.',
            'room_id.exists'               => 'Выбранный номер не найден или недоступен.',
            'check_in_date.required'       => 'Укажите дату заезда.',
            'check_in_date.after_or_equal' => 'Дата заезда не может быть в прошлом.',
            'check_out_date.required'      => 'Укажите дату выезда.',
            'check_out_date.after'         => 'Дата выезда должна быть позже даты заезда.',
            'guests_count.min'             => 'Количество гостей должно быть не менее 1.',
            'guests_count.max'             => 'Количество гостей не может превышать 20.',
            'special_requests.max'         => 'Особые пожелания не должны превышать 1000 символов.',
        ];
    }

    /**
     * Читаемые названия атрибутов для вывода в сообщениях.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'room_id'          => 'номер отеля',
            'check_in_date'    => 'дата заезда',
            'check_out_date'   => 'дата выезда',
            'guests_count'     => 'количество гостей',
            'special_requests' => 'особые пожелания',
        ];
    }

    /**
     * Возвращает JSON-ответ с ошибками при провале валидации.
     * Включает correlation_id для трассировки.
     */
    protected function failedValidation(Validator $validator): never
    {
        $correlationId = $this->header('X-Correlation-ID') ?? Uuid::uuid4()->toString();

        throw new HttpResponseException(
            new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => 'Ошибка валидации запроса на бронирование.',
                'errors'         => $validator->errors(),
                'correlation_id' => $correlationId,
            ], 422)
        );
    }
}
