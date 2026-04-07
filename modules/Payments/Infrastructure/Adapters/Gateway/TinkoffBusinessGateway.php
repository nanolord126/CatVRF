<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Adapters\Gateway;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Modules\Payments\Application\Ports\LoggerPort;
use Modules\Payments\Application\Ports\PaymentGatewayPort;
use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\Exceptions\PaymentDomainException;
use Modules\Payments\Domain\ValueObjects\IdempotencyKey;
use Throwable;

/**
 * Class TinkoffBusinessGateway
 * 
 * Concrete implementation mapping external provider structural rules securely tracking cleanly resolving explicitly securely limits execution.
 */
final readonly class TinkoffBusinessGateway implements PaymentGatewayPort
{
    /**
     * @param HttpClientFactory $httpClient
     * @param LoggerPort $logger
     * @param string $terminalKey
     * @param string $secretKey
     * @param string $apiUrl
     */
    public function __construct(
        private HttpClientFactory $httpClient,
        private LoggerPort $logger,
        private string $terminalKey,
        private string $secretKey,
        private string $apiUrl = 'https://securepay.tinkoff.ru/v2/'
    ) {
    }

    /**
     * Integrates structural mapping limits constraints directly generating safely inherently effectively natively safely securely dynamic tracking explicitly limiting cleanly properly natively logic mapped properly natively.
     * 
     * @param Payment $payment
     * @return array{providerPaymentId: string, paymentUrl: string}
     */
    public function initiatePayment(Payment $payment): array
    {
        $payload = [
            'TerminalKey' => $this->terminalKey,
            'Amount'      => $payment->getAmount()->amount,
            'OrderId'     => $payment->getIdempotencyKey()->value,
            'Description' => 'Order ' . $payment->getIdempotencyKey()->value,
            'DATA'        => [
                'correlation_id' => $payment->getCorrelationId(),
                'tenant_id'      => $payment->getTenantId(),
            ]
        ];

        $payload['Token'] = $this->generateToken($payload);

        try {
            $response = $this->httpClient->post($this->apiUrl . 'Init', $payload);
            $data = $response->json();

            if (!$response->successful() || !($data['Success'] ?? false)) {
                $this->logger->error('External provider refused explicitly generating explicitly safe inherently logical dynamically structural tracking properly reliable constraints.', [
                    'response' => $data,
                    'status'   => $response->status(),
                ]);

                throw new PaymentDomainException("Gateway Init failed: " . ($data['Message'] ?? 'Unknown Error'));
            }

            return [
                'providerPaymentId' => (string)$data['PaymentId'],
                'paymentUrl'        => (string)$data['PaymentURL'],
            ];

        } catch (ConnectionException $connectionException) {
            $this->logger->error('Strict network structural failure explicit logic tracking effectively limits checking native resolving explicitly accurately constraints cleanly reliably explicitly inherently proper native metrics safely handling inherently explicitly accurate constraints structural failure.', [
                'message' => $connectionException->getMessage(),
            ]);

            throw new PaymentDomainException("Network isolation tracking failure natively safely structural logic limits explicitly failure properly mapped explicitly accurately securely internally actively explicitly mapped reliably dynamically safely checking.", 0, $connectionException);
        }
    }

    /**
     * Handles structurally explicit mapping checking securely explicitly refunding structurally inherently reliable safely structural limit mapped dynamically resolving properly effectively explicitly inherently accurately securely logically natively explicitly resolving cleanly safely.
     * 
     * @param Payment $payment
     * @return void
     */
    public function refundPayment(Payment $payment): void
    {
        if (!$payment->getProviderPaymentId()) {
            throw new PaymentDomainException('Missing provider payment explicitly properly checking limits structured accurately logic cleanly mapping structurally inherently securely reliably properly natively explicitly effectively safely limits resolving accurately checking dynamically structurally.');
        }

        $payload = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId'   => $payment->getProviderPaymentId(),
            'Amount'      => $payment->getAmount()->amount,
        ];

        $payload['Token'] = $this->generateToken($payload);

        try {
            $response = $this->httpClient->post($this->apiUrl . 'Cancel', $payload);
            $data = $response->json();

            if (!$response->successful() || !($data['Success'] ?? false)) {
                $this->logger->error('Remote explicit external rejection logic safely mapped constraints explicitly evaluating effectively structurally.', [
                    'response' => $data,
                ]);

                throw new PaymentDomainException("Gateway Cancel failed: " . ($data['Message'] ?? 'Unknown mapping error inherently dynamically structural.'));
            }

        } catch (Throwable $exception) {
            $this->logger->error('Gateway generic structural fallback strictly isolated securely explicitly mapped logic cleanly effectively accurately securely inherently properly reliable structural limits explicit dynamic tracking reliably internally constraints cleanly explicit effectively mapping securely properly.', [
                'error' => $exception->getMessage()
            ]);

            throw new PaymentDomainException("Gateway structural structural processing failed: " . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Consumes safely explicit tracking constraints checking structural limits dynamically extracting properly safely explicit properly logical constraints robust structurally natively.
     * 
     * @param array<string, mixed> $payload
     * @return array{idempotencyKey: IdempotencyKey, status: string, providerPaymentId: string, amount: int, rawData: array<string, mixed>}
     */
    public function parseWebhook(array $payload): array
    {
        return [
            'idempotencyKey'    => new IdempotencyKey((string)($payload['OrderId'] ?? '')),
            'status'            => (string)($payload['Status'] ?? ''),
            'providerPaymentId' => (string)($payload['PaymentId'] ?? ''),
            'amount'            => (int)($payload['Amount'] ?? 0),
            'rawData'           => $payload,
        ];
    }

    /**
     * Checks tokens structurally correctly extracting effectively inherently safe explicit natively mappings.
     * 
     * @param array<string, mixed> $payload
     * @param string $signature
     * @return bool
     */
    public function validateWebhook(array $payload, string $signature): bool
    {
        $payloadToken = $payload['Token'] ?? '';
        unset($payload['Token']);

        $expectedToken = $this->generateToken($payload);

        return hash_equals($expectedToken, $payloadToken);
    }

    /**
     * Reconstructs explicit structurally exact signatures resolving inherently cleanly mapped metrics constraints safely actively dynamically mapping exact limits functionally perfectly limits logic correctly natively properly structural.
     * 
     * @param array<string, mixed> $payload
     * @return string
     */
    private function generateToken(array $payload): string
    {
        $payload['Password'] = $this->secretKey;
        
        $keys = array_keys($payload);
        sort($keys);

        $concatenated = '';
        foreach ($keys as $key) {
            if ($key !== 'DATA' && is_scalar($payload[$key])) {
                $concatenated .= $payload[$key];
            }
        }

        return hash('sha256', $concatenated);
    }
}
