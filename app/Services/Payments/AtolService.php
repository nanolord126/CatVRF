<?php

namespace App\Services\Payments;

use Atol\Online\Api\v4\AtolClient;
use Atol\Online\Api\v4\Response\TokenResponse;
use Illuminate\Support\Facades\Log;

class AtolService
{
    protected AtolClient $client;
    protected string $login;
    protected string $password;
    protected bool $isTestMode;
    protected string $groupCode;

    public function __construct()
    {
        // Канон 2026: Данные инжектятся через Doppler в config
        $this->login = config('payments.ofd.atol.login');
        $this->password = config('payments.ofd.atol.password');
        $this->groupCode = config('payments.ofd.atol.group_code', 'v4-online-atol-ru');
        $this->isTestMode = config('payments.ofd.atol.test_mode', true);

        // Инициализация клиента Atol (atol/online-api-php-client)
        $this->client = new AtolClient($this->isTestMode ? AtolClient::URL_TEST : AtolClient::URL_PROD);
    }

    /**
     * Регистрация чека прихода (Sell)
     * 
     * @param string $externalId Уникальный ID транзакции (correlation_id)
     * @param string $email Email покупателя
     * @param array $items Массив позиций ['name', 'price', 'quantity']
     * @param float $total Сумма чека
     */
    public function registerSell(string $externalId, string $email, array $items, float $total): ?string
    {
        $token = $this->getToken();
        if (!$token || empty($token)) {
             Log::error('ATOL: Token is missing or invalid.');
             return null;
        }

        try {
            // Формирование чека по канону 2026
            $receipt = new \Atol\Online\Api\v4\Request\Receipt();
            $receipt->setExternalId($externalId);
            $receipt->getAttributes()->setEmail($email);

            foreach ($items as $item) {
                // При VAT_20 (тип 5 в старом API, 20% в новом)
                $receiptItem = new \Atol\Online\Api\v4\Request\ReceiptItem(
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    \Atol\Online\Api\v4\Request\ReceiptItem::VAT_20
                );
                $receipt->addItem($receiptItem);
            }

            // Добавляем оплату (тип 1 - электронными)
            $receipt->addPayment(1, $total);

            /** @var \Atol\Online\Api\v4\Response\ReceiptResponse $response */
            $response = $this->client->sell($this->groupCode, $receipt, $token);

            if ($response && $response->getError() === null) {
                Log::info('ATOL Receipt Registered', [
                    'uuid' => $response->getUuid(),
                    'external_id' => $externalId,
                    'correlation_id' => request()->header('X-Correlation-ID', $externalId)
                ]);
                return $response->getUuid();
            }

            Log::error('ATOL Sell Error', [
                'message' => $response->getError()->getMessage(),
                'code' => $response->getError()->getCode(),
                'correlation_id' => request()->header('X-Correlation-ID')
            ]);
            
            return null;

        } catch (\Exception $e) {
            Log::critical('ATOL Sell Exception: ' . $e->getMessage(), [
                 'correlation_id' => request()->header('X-Correlation-ID')
            ]);
            return null;
        }
    }

    /**
     * Получение токена авторизации
     */
    public function getToken(): ?string
    {
        try {
            /** @var TokenResponse $response */
            $response = $this->client->getToken($this->login, $this->password);
            
            if ($response->getError() === null) {
                return $response->getToken();
            }

            Log::error('ATOL Token Error: ' . $response->getError()->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::critical('ATOL Connection Failed: ' . $e->getMessage(), [
                'correlation_id' => request()->header('X-Correlation-ID')
            ]);
            return null;
        }
    }
}
