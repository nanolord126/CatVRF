<?php declare(strict_types=1);

namespace App\Services\CRM;

use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Cache\Repository;

/**
 * CRM Integration Service
 * 
 * Integrates with external CRM systems (HubSpot, Salesforce, AmoCRM, Bitrix24).
 * Syncs contacts, bookings, and customer journey data.
 */
final readonly class CRMIntegrationService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Repository $cache,
    ) {}

    /**
     * Update or create contact in CRM system.
     * 
     * @param array $contactData Contact and booking data
     * @return string|null CRM contact ID
     */
    public function updateOrCreateContact(array $contactData): ?string
    {
        $provider = config('services.crm.provider', 'hubspot');
        
        try {
            return match ($provider) {
                'hubspot' => $this->syncToHubSpot($contactData),
                'salesforce' => $this->syncToSalesforce($contactData),
                'amocrm' => $this->syncToAmoCRM($contactData),
                'bitrix24' => $this->syncToBitrix24($contactData),
                default => $this->syncToCustomCRM($contactData),
            };
        } catch (\Throwable $e) {
            $this->logger->error('CRM sync failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'contact_data' => $contactData,
                'correlation_id' => $contactData['correlation_id'] ?? null,
            ]);

            throw $e;
        }
    }

    /**
     * Sync contact to HubSpot.
     */
    private function syncToHubSpot(array $data): ?string
    {
        $apiKey = config('services.crm.hubspot.api_key');
        $endpoint = config('services.crm.hubspot.endpoint');

        $hubSpotData = [
            'properties' => [
                'email' => $data['email'] ?? '',
                'firstname' => $data['first_name'] ?? '',
                'lastname' => $data['last_name'] ?? '',
                'phone' => $data['phone'] ?? '',
                'tourism_booking_status' => $data['booking_status'] ?? '',
                'tour_name' => $data['tour_name'] ?? '',
                'tour_destination' => $data['tour_destination'] ?? '',
                'start_date' => $data['start_date'] ?? '',
                'end_date' => $data['end_date'] ?? '',
                'person_count' => (string) ($data['person_count'] ?? 0),
                'total_amount' => (string) ($data['total_amount'] ?? 0),
                'is_b2b_customer' => $data['is_b2b'] ? 'true' : 'false',
            ],
        ];

        if ($data['contact_id'] ?? null) {
            $response = Http::withToken($apiKey)
                ->patch("{$endpoint}/contacts/{$data['contact_id']}", $hubSpotData);
        } else {
            $response = Http::withToken($apiKey)
                ->post("{$endpoint}/contacts", $hubSpotData);
        }

        if (!$response->successful()) {
            throw new \RuntimeException('HubSpot sync failed: ' . $response->body());
        }

        return $response->json('id');
    }

    /**
     * Sync contact to Salesforce.
     */
    private function syncToSalesforce(array $data): ?string
    {
        $accessToken = $this->getSalesforceAccessToken();
        $endpoint = config('services.crm.salesforce.endpoint');

        $salesforceData = [
            'Email' => $data['email'] ?? '',
            'FirstName' => $data['first_name'] ?? '',
            'LastName' => $data['last_name'] ?? '',
            'Phone' => $data['phone'] ?? '',
            'Tourism_Booking_Status__c' => $data['booking_status'] ?? '',
            'Tour_Name__c' => $data['tour_name'] ?? '',
            'Tour_Destination__c' => $data['tour_destination'] ?? '',
            'Start_Date__c' => $data['start_date'] ?? '',
            'End_Date__c' => $data['end_date'] ?? '',
            'Person_Count__c' => $data['person_count'] ?? 0,
            'Total_Amount__c' => $data['total_amount'] ?? 0,
            'Is_B2B_Customer__c' => $data['is_b2b'] ?? false,
        ];

        if ($data['contact_id'] ?? null) {
            $response = Http::withToken($accessToken)
                ->patch("{$endpoint}/sobjects/Contact/{$data['contact_id']}", $salesforceData);
        } else {
            $response = Http::withToken($accessToken)
                ->post("{$endpoint}/sobjects/Contact", $salesforceData);
        }

        if (!$response->successful()) {
            throw new \RuntimeException('Salesforce sync failed: ' . $response->body());
        }

        return $response->json('id');
    }

    /**
     * Sync contact to AmoCRM.
     */
    private function syncToAmoCRM(array $data): ?string
    {
        $accessToken = $this->getAmoCRMAccessToken();
        $endpoint = config('services.crm.amocrm.endpoint');

        $amoData = [
            'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'custom_fields_values' => [
                [
                    'field_code' => 'EMAIL',
                    'values' => [['value' => $data['email'] ?? '']],
                ],
                [
                    'field_code' => 'PHONE',
                    'values' => [['value' => $data['phone'] ?? '']],
                ],
                [
                    'field_id' => config('services.crm.amocrm.fields.booking_status'),
                    'values' => [['value' => $data['booking_status'] ?? '']],
                ],
                [
                    'field_id' => config('services.crm.amocrm.fields.tour_name'),
                    'values' => [['value' => $data['tour_name'] ?? '']],
                ],
                [
                    'field_id' => config('services.crm.amocrm.fields.total_amount'),
                    'values' => [['value' => $data['total_amount'] ?? 0]],
                ],
            ],
        ];

        if ($data['contact_id'] ?? null) {
            $response = Http::withToken($accessToken)
                ->patch("{$endpoint}/api/v4/contacts/{$data['contact_id']}", $amoData);
        } else {
            $response = Http::withToken($accessToken)
                ->post("{$endpoint}/api/v4/contacts", $amoData);
        }

        if (!$response->successful()) {
            throw new \RuntimeException('AmoCRM sync failed: ' . $response->body());
        }

        return $response->json('_embedded.contacts.0.id');
    }

    /**
     * Sync contact to Bitrix24.
     */
    private function syncToBitrix24(array $data): ?string
    {
        $webhookUrl = config('services.crm.bitrix24.webhook_url');
        $endpoint = config('services.crm.bitrix24.domain');

        $bitrixData = [
            'fields' => [
                'EMAIL' => [['VALUE' => $data['email'] ?? '', 'VALUE_TYPE' => 'WORK']],
                'PHONE' => [['VALUE' => $data['phone'] ?? '', 'VALUE_TYPE' => 'WORK']],
                'NAME' => $data['first_name'] ?? '',
                'LAST_NAME' => $data['last_name'] ?? '',
                'UF_CRM_BOOKING_STATUS' => $data['booking_status'] ?? '',
                'UF_CRM_TOUR_NAME' => $data['tour_name'] ?? '',
                'UF_CRM_TOUR_DESTINATION' => $data['tour_destination'] ?? '',
                'UF_CRM_START_DATE' => $data['start_date'] ?? '',
                'UF_CRM_END_DATE' => $data['end_date'] ?? '',
                'UF_CRM_PERSON_COUNT' => $data['person_count'] ?? 0,
                'UF_CRM_TOTAL_AMOUNT' => $data['total_amount'] ?? 0,
                'UF_CRM_IS_B2B' => $data['is_b2b'] ? 'Y' : 'N',
            ],
            'params' => ['REGISTER_SONET_EVENT' => 'Y'],
        ];

        if ($data['contact_id'] ?? null) {
            $response = Http::post("{$endpoint}/crm.contact.update", array_merge($bitrixData, ['id' => $data['contact_id']]));
        } else {
            $response = Http::post("{$endpoint}/crm.contact.add", $bitrixData);
        }

        if (!$response->successful()) {
            throw new \RuntimeException('Bitrix24 sync failed: ' . $response->body());
        }

        return $response->json('result');
    }

    /**
     * Sync to custom CRM.
     */
    private function syncToCustomCRM(array $data): ?string
    {
        $endpoint = config('services.crm.custom.endpoint');
        $apiKey = config('services.crm.custom.api_key');

        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post($endpoint, $data);

        if (!$response->successful()) {
            throw new \RuntimeException('Custom CRM sync failed: ' . $response->body());
        }

        return $response->json('contact_id');
    }

    /**
     * Get Salesforce access token.
     */
    private function getSalesforceAccessToken(): string
    {
        $cacheKey = 'crm:salesforce:access_token';
        $cached = $this->cache->get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $response = Http::asForm()->post(config('services.crm.salesforce.token_url'), [
            'grant_type' => 'password',
            'client_id' => config('services.crm.salesforce.client_id'),
            'client_secret' => config('services.crm.salesforce.client_secret'),
            'username' => config('services.crm.salesforce.username'),
            'password' => config('services.crm.salesforce.password'),
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Salesforce token request failed');
        }

        $token = $response->json('access_token');
        $expiresIn = $response->json('expires_in', 3600);

        $this->cache->put($cacheKey, $token, $expiresIn - 60);

        return $token;
    }

    /**
     * Get AmoCRM access token.
     */
    private function getAmoCRMAccessToken(): string
    {
        $cacheKey = 'crm:amocrm:access_token';
        $cached = $this->cache->get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $response = Http::asForm()->post(config('services.crm.amocrm.token_url'), [
            'client_id' => config('services.crm.amocrm.client_id'),
            'client_secret' => config('services.crm.amocrm.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => config('services.crm.amocrm.auth_code'),
            'redirect_uri' => config('services.crm.amocrm.redirect_uri'),
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('AmoCRM token request failed');
        }

        $token = $response->json('access_token');
        $expiresIn = $response->json('expires_in', 86400);

        \Ioken =l$responuemijsnn('accate\topport\Facades\Cache::put($cacheKey, $token, $expiresIn - 60);

        return $token;
    }
}
