<?php declare(strict_types=1);

namespace Modules\Finances\Services\QR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UniversalQRService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private string $phoneNumber;
        private string $bankName;
    
        public function __construct()
        {
            $this->phoneNumber = config('payments.sbp.phone_number');
            $this->bankName = config('payments.sbp.bank_name', 'Tinkoff');
        }
    
        /**
         * Сгенерировать статический QR-код.
         */
        public function generateStaticQR(): array
        {
            try {
                $qrData = $this->buildQRString(null, null);
                Log::info('Static QR generated', ['phone_number' => $this->phoneNumber]);
                return [
                    'type' => 'static',
                    'qr_data' => $qrData,
                    'qr_image' => $this->generateQRImage($qrData),
                ];
            } catch (Exception $e) {
                Log::error('Static QR generation failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Сгенерировать динамический QR с суммой.
         */
        public function generateDynamicQR(float $amount, string $orderId, string $description = null): array
        {
            try {
                if ($amount <= 0 || $amount > 1000000) {
                    throw new Exception("Invalid amount: {$amount}");
                }
    
                $qrData = $this->buildQRString($amount, $orderId, $description);
                Log::info('Dynamic QR generated', ['amount' => $amount, 'order_id' => $orderId]);
                
                return [
                    'type' => 'dynamic',
                    'amount' => $amount,
                    'order_id' => $orderId,
                    'qr_data' => $qrData,
                    'qr_image' => $this->generateQRImage($qrData),
                ];
            } catch (Exception $e) {
                Log::error('Dynamic QR generation failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Генерировать QR для заказа.
         */
        public function generateForOrder(array $data): array
        {
            return $this->generateDynamicQR(
                $data['amount'],
                $data['order_id'],
                $data['purpose'] ?? 'Payment'
            );
        }
    
        /**
         * Построить строку QR в формате SBP.
         */
        private function buildQRString(?float $amount = null, ?string $orderId = null, ?string $description = null): string
        {
            $qrString = 'https://qr.nspk.ru/?phone=' . $this->cleanPhoneNumber($this->phoneNumber);
    
            if ($amount !== null && $amount > 0) {
                $qrString .= '&sum=' . (int) ($amount * 100);
            }
    
            if ($orderId) {
                $qrString .= '&ref=' . urlencode($orderId);
            }
    
            if ($description) {
                $qrString .= '&nm=' . urlencode(substr($description, 0, 300));
            }
    
            return $qrString;
        }
    
        /**
         * Очистить номер телефона.
         */
        private function cleanPhoneNumber(string $phone): string
        {
            $cleaned = preg_replace('/\D/', '', $phone);
            if (strlen($cleaned) === 10) {
                $cleaned = '7' . $cleaned;
            }
            return $cleaned;
        }
    
        /**
         * Сгенерировать изображение QR.
         */
        private function generateQRImage(string $qrData): string
        {
            return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData);
        }
    
        /**
         * Генерировать статический QR для витрины.
         */
        public function generateStatic(array $merchant): string
        {
            return "https://qr.nspk.ru/p2p/{$merchant['sbp_id']}?type=static";
        }
    
        /**
         * Валидировать QR данные.
         */
        public function validateQRData(string $qrData): bool
        {
            return str_starts_with($qrData, 'https://qr.nspk.ru/');
        }
    
        /**
         * Получить инф о сервисе.
         */
        public function getInfo(): array
        {
            return [
                'name' => 'UniversalQRService',
                'provider' => 'SBP',
                'bank_name' => $this->bankName,
                'supported_types' => ['static', 'dynamic'],
            ];
        }
}
