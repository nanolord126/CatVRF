<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\FraudControlService;
use Illuminate\Support\Str;

final class BaseApiRequest extends FormRequest
{
    /**
     * Проверка авторизации.
     * По умолчанию требуется authenticated пользователь.
     * Переопределить в наследующих классах для специфических правил.
     */
    public function authorize(): bool
    {
        // CANON 2026: Fraud Check in FormRequest
        if ($this->guard->check()) {
            $correlationId = $this->header('X-Correlation-ID') ?? Str::uuid()->toString();
            $fraudResult = app(FraudControlService::class)->check(
                (int) $this->guard->id(),
                'form_request',
                (int) ($this->input('amount', 0)),
                $this->ip(),
                $this->header('X-Device-Fingerprint'),
                $correlationId,
            );
            if ($fraudResult['decision'] === 'block') {
                $this->logger->channel('fraud_alert')->warning('FormRequest blocked', [
                    'class'          => __CLASS__,
                    'correlation_id' => $correlationId,
                    'score'          => $fraudResult['score'],
                ]);

                return false;
            }
        }

        return $this->guard->check();
    }

    /**
     * Получить все данные с дополнительными служебными полями.
     */
    public function allWithMetadata(): array
    {
        return [
            ...parent::all(),
            'correlation_id' => $this->header('X-Correlation-ID') ?? Str::uuid()->toString(),
            'idempotency_key' => $this->header('Idempotency-Key'),
        ];
    }

    /**
     * Получить correlation_id из заголовка или генерировать новый.
     */
    public function getCorrelationId(): string
    {
        return $this->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

    /**
     * Получить idempotency_key из заголовка.
     */
    public function getIdempotencyKey(): ?string
    {
        return $this->header('Idempotency-Key');
    }

    /**
     * Обработчик ошибок валидации для API.
     *
     * Возвращает JSON ответ с деталями ошибок и correlation_id.
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, $this->responseFactory->json([
            'error' => 'Validation failed',
            'errors' => $validator->errors()->toArray(),
            'correlation_id' => $this->header('X-Correlation-ID') ?? '',
        ], 422));
    }
}
