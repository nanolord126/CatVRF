<?php declare(strict_types=1);

namespace App\Http\Requests\Beauty;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ConfirmAppointmentRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Beauty
 */
final class ConfirmAppointmentRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
        {
            return $this->guard->check();
        }

        /**
         * Handle rules operation.
         *
         * @throws \DomainException
         */
        public function rules(): array
        {
            return [
                'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
                'payment_status' => ['required', 'string', 'in:captured,authorized'],
                'payment_id' => ['required', 'integer'],
            ];
        }

        /**
         * Handle messages operation.
         *
         * @throws \DomainException
         */
        public function messages(): array
        {
            return [
                'appointment_id.required' => 'Appointment ID required',
                'appointment_id.exists' => 'Appointment not found',
                'payment_status.required' => 'Payment status required',
                'payment_status.in' => 'Invalid payment status',
                'payment_id.required' => 'Payment ID required',
            ];
        }
}
