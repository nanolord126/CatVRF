<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

abstract class BaseApiRequest extends FormRequest
{
    /**
     * Проверка авторизации.
     * По умолчанию требуется authenticated пользователь.
     * Переопределить в наследующих классах для специфических правил.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // CANON 2026: Fraud Check in FormRequest
        if (class_exists(\App\Services\Fraud\FraudControlService::class) && auth()->check()) {
            $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
            if ($fraudScore > 0.7 && !auth()->user()->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => $fraudScore]);
                return false;
            }
        }
        return auth()->check();
    }
    
    /**
     * Обработчик ошибок валидации для API.
     *
     * Возвращает JSON ответ с деталями ошибок и correlation_id.
     *
     * @param Validator $validator
     * @return void
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, response()->json([
            'error' => 'Validation failed',
            'errors' => $validator->errors()->toArray(),
            'correlation_id' => $this->header('X-Correlation-ID') ?? '',
        ], 422));
    }
    
    /**
     * Получить все данные с дополнительными служебными полями.
     *
     * @return array
     */
    public function allWithMetadata(): array
    {
        return [
            ...parent::all(),
            'correlation_id' => $this->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString(),
            'idempotency_key' => $this->header('Idempotency-Key'),
        ];
    }
    
    /**
     * Получить correlation_id из заголовка или генерировать новый.
     *
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
    }
    
    /**
     * Получить idempotency_key из заголовка.
     *
     * @return string|null
     */
    public function getIdempotencyKey(): ?string
    {
        return $this->header('Idempotency-Key');
    }
}
