<?php

declare(strict_types=1);

namespace Modules\Commissions\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Commissions\Application\DTOs\CommissionData;
use App\Services\FraudControlService;
use App\Models\Tenant;

final class CalculateCommissionRequest extends FormRequest
{
    public function authorize(FraudControlService $fraudControlService): bool
    {
        // Здесь должна быть проверка прав пользователя на выполнение этого действия
        // Например, $this->user()->can('calculate', Commission::class);
        // А также проверка на фрод
        $fraudControlService->check();
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'sometimes|integer|exists:tenants,id',
            'amount' => 'required|integer|min:1',
            'vertical' => 'required|string|max:255',
            'source_type' => 'required|string|max:255',
            'source_id' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Сумма для расчета комиссии обязательна.',
            'amount.integer' => 'Сумма должна быть целым числом в копейках.',
            'vertical.required' => 'Вертикаль обязательна для расчета комиссии.',
        ];
    }

    public function toData(string $correlationId): CommissionData
    {
        return new CommissionData(
            tenant_id: $this->input('tenant_id', Tenant::current()->id),
            amount: (int) $this->input('amount'),
            vertical: $this->input('vertical'),
            source_type: $this->input('source_type'),
            source_id: (int) $this->input('source_id'),
            correlation_id: $correlationId
        );
    }
}
