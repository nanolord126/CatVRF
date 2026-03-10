<?php

namespace App\Services\B2B;

use App\Models\B2B\PurchaseOrder;
use App\Models\B2B\Supplier;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class B2BWalletPaymentService
{
    /**
     * Оплата заказа поставщику через встроенный B2B кошелек организации
     */
    public function payPurchaseOrder(PurchaseOrder $order)
    {
        if ($order->payment_status === 'PAID') {
            throw new \Exception("Заказ #{$order->order_number} уже оплачен.");
        }

        $tenant = auth('tenant')->user(); // В контексте тенанта это владелец/организация
        $supplier = $order->supplier;

        return DB::transaction(function () use ($order, $tenant, $supplier) {
            // 1. Проверяем баланс организации (используем bavix/laravel-wallet)
            if ($tenant->balance < $order->total_amount) {
                // Если баланс недостаточен, проверяем B2B кредитный лимит
                $remainingLimit = $supplier->credit_limit - $this->getOccupiedCredit($supplier);
                
                if ($remainingLimit < $order->total_amount) {
                    Notification::make()
                        ->title('Ошибка оплаты B2B')
                        ->body('Недостаточно средств на кошельке и исчерпан кредитный лимит поставщика.')
                        ->danger()
                        ->send();
                    return false;
                }
            }

            // 2. Проводим транзакцию: Списание с кошелька организации
            // В 2026 году это также генерирует фискальный чек через интеграцию
            $tenant->withdraw($order->total_amount, [
                'description' => "Оплата заказа на закупку #{$order->order_number}",
                'order_id' => $order->id,
                'supplier_id' => $supplier->id,
                'correlation_id' => $order->correlation_id,
            ]);

            // 3. Обновляем статус заказа
            $order->update([
                'payment_status' => 'PAID',
                'status' => 'APPROVED' // После оплаты заказ уходит в работу поставщику
            ]);

            Notification::make()
                ->title('Заказ оплачен успешно')
                ->body("Сумма {$order->total_amount} списана с B2B кошелька.")
                ->success()
                ->send();

            return true;
        });
    }

    /** Расчет занятого кредитного лимита */
    protected function getOccupiedCredit(Supplier $supplier)
    {
        return PurchaseOrder::where('supplier_id', $supplier->id)
            ->where('payment_status', 'UNPAID')
            ->sum('total_amount');
    }
}
