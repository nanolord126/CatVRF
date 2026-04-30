<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Adapters;

use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Honesty Mark (Честный ЗНАК) Adapter for Supermarket vertical.
 * 
 * Provides integration with the Russian government's track and trace system
 * for regulated products (alcohol, tobacco, medicines, dairy, water).
 * 
 * Features:
 * - Validate product data matrix codes
 * - Withdraw marks from the system
 * - Batch operations for efficiency
 */
final class HonestyMarkAdapter
{
    use WithTelemetry;

    private string $apiKey;
    private string $endpoint;
    private string $certificatePath;
    private string $certificatePassword;

    public function __construct()
    {
        $this->apiKey = config('supermarket.honesty_mark.api_key', '');
        $this->endpoint = config('supermarket.honesty_mark.endpoint', 'https://markirovka.crpt.ru/api/v3');
        $this->certificatePath = config('supermarket.honesty_mark.certificate_path', '');
        $this->certificatePassword = config('supermarket.honesty_mark.certificate_password', '');
    }

    /**
     * Validate a product data matrix code.
     */
    public function validateMark(string $dataMatrix, string $gtin): array
    {
        return $this->withSpan(
            'honesty_mark.validate',
            function () use ($dataMatrix, $gtin) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post("{$this->endpoint}/lk/documents/create", [
                            'document_format' => 'MANUAL',
                            'type' => 'CHECK_VERIFICATION',
                            'product_document' => [
                                'doc_id' => uniqid(),
                                'doc_date' => now()->format('Y-m-d'),
                                'doc_type' => 'CHECK_VERIFICATION',
                                'product_group' => $this->determineProductGroup($gtin),
                            ],
                            'products' => [
                                [
                                    'cis' => $dataMatrix,
                                    'gtin' => $gtin,
                                ],
                            ],
                        ]);

                    if (!$response->successful()) {
                        Log::error('Honesty Mark validation failed', [
                            'status' => $response->status(),
                            'gtin' => $gtin,
                            'body' => $response->body(),
                        ]);
                        
                        return [
                            'valid' => false,
                            'error' => 'API request failed',
                            'status_code' => $response->status(),
                        ];
                    }

                    $data = $response->json();
                    
                    return [
                        'valid' => $data['valid'] ?? false,
                        'status' => $data['status'] ?? 'unknown',
                        'product_info' => $data['product_info'] ?? null,
                    ];
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Honesty Mark validation error', [
                        'gtin' => $gtin,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return [
                        'valid' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'honesty_mark_validate',
            ),
        );
    }

    /**
     * Withdraw a mark from the system (for sold items).
     */
    public function withdrawMark(string $dataMatrix, string $gtin, string $orderId): array
    {
        return $this->withSpan(
            'honesty_mark.withdraw',
            function () use ($dataMatrix, $gtin, $orderId) {
                try {
                    $response = Http::timeout(15)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post("{$this->endpoint}/lk/documents/create", [
                            'document_format' => 'MANUAL',
                            'type' => 'CONSIGNMENT_DOCUMENT',
                            'product_document' => [
                                'doc_id' => "ORDER_{$orderId}",
                                'doc_date' => now()->format('Y-m-d'),
                                'doc_type' => 'CONSIGNMENT_DOCUMENT',
                                'product_group' => $this->determineProductGroup($gtin),
                            ],
                            'products' => [
                                [
                                    'cis' => $dataMatrix,
                                    'gtin' => $gtin,
                                    'quantity' => 1,
                                ],
                            ],
                        ]);

                    if (!$response->successful()) {
                        Log::error('Honesty Mark withdrawal failed', [
                            'status' => $response->status(),
                            'order_id' => $orderId,
                            'gtin' => $gtin,
                        ]);
                        
                        return [
                            'success' => false,
                            'error' => 'API request failed',
                        ];
                    }

                    $data = $response->json();
                    
                    return [
                        'success' => true,
                        'document_id' => $data['document_id'] ?? null,
                        'status' => $data['status'] ?? 'pending',
                    ];
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Honesty Mark withdrawal error', [
                        'order_id' => $orderId,
                        'gtin' => $gtin,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'honesty_mark_withdraw',
            ),
        );
    }

    /**
     * Batch withdraw multiple marks.
     */
    public function batchWithdraw(array $marks, string $orderId): array
    {
        return $this->withSpan(
            'honesty_mark.batch_withdraw',
            function () use ($marks, $orderId) {
                $results = [];
                
                foreach (array_chunk($marks, 100) as $chunk) {
                    $products = array_map(function ($mark) {
                        return [
                            'cis' => $mark['data_matrix'],
                            'gtin' => $mark['gtin'],
                            'quantity' => $mark['quantity'] ?? 1,
                        ];
                    }, $chunk);

                    try {
                        $response = Http::timeout(30)
                            ->withHeaders([
                                'Authorization' => 'Bearer ' . $this->apiKey,
                                'Content-Type' => 'application/json',
                            ])
                            ->post("{$this->endpoint}/lk/documents/create", [
                                'document_format' => 'MANUAL',
                                'type' => 'CONSIGNMENT_DOCUMENT',
                                'product_document' => [
                                    'doc_id' => "ORDER_{$orderId}",
                                    'doc_date' => now()->format('Y-m-d'),
                                    'doc_type' => 'CONSIGNMENT_DOCUMENT',
                                    'product_group' => 'FOOD_PRODUCTS',
                                ],
                                'products' => $products,
                            ]);

                        if ($response->successful()) {
                            $data = $response->json();
                            $results[] = [
                                'chunk' => count($chunk),
                                'success' => true,
                                'document_id' => $data['document_id'] ?? null,
                            ];
                        } else {
                            $results[] = [
                                'chunk' => count($chunk),
                                'success' => false,
                                'error' => 'API request failed',
                            ];
                        }
                    } catch (\Exception $e) {
                        $this->recordSpanException($e);
                        $results[] = [
                            'chunk' => count($chunk),
                            'success' => false,
                            'error' => $e->getMessage(),
                        ];
                    }
                }

                return [
                    'total_marks' => count($marks),
                    'successful_chunks' => count(array_filter($results, fn($r) => $r['success'])),
                    'results' => $results,
                ];
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'honesty_mark_batch_withdraw',
            ),
        );
    }

    /**
     * Determine product group based on GTIN prefix.
     */
    private function determineProductGroup(string $gtin): string
    {
        $prefix = substr($gtin, 0, 3);
        
        return match ($prefix) {
            '046', '047' => 'ALCOHOL_MARKED',
            '048' => 'TOBACCO_MARKED',
            '043', '044' => 'MEDICINES',
            '045' => 'MEDICAL_DEVICES',
            '049' => 'FOOD_PRODUCTS',
            default => 'FOOD_PRODUCTS',
        };
    }
}
