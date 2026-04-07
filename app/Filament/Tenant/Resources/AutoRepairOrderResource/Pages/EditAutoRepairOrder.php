<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoRepairOrderResource\Pages;


use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\EditRecord;

final class EditAutoRepairOrder extends EditRecord
{

    protected static string $resource = AutoRepairOrderResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ];
        }

        protected function mutateFormDataBeforeSave(array $data): array
        {
            // Пересчет итоговой суммы в копейках при редактировании
            $labor = (int) ($data['labor_cost_kopecks'] ?? 0);
            $parts = (int) ($data['parts_cost_kopecks'] ?? 0);

            $data['total_cost_kopecks'] = $labor + $parts;

            return $data;
        }

        protected function afterSave(): void
        {
            // Обновляем статус авто если статус заказа завершен или отменен
            $order = $this->getRecord();
            $vehicle = Vehicle::find($order->vehicle_id);

            if ($vehicle && in_array($order->status, ['completed', 'cancelled'])) {
                $vehicle->update(['status' => 'active']);
            }

            activity()
                ->performedBy($this->guard->user())
                ->on($order)
                ->withProperty('correlation_id', $order->correlation_id)
                ->withProperty('final_status', $order->status)
                ->log('Auto repair order updated');
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
