<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\Services\RealEstateBlockchainVerificationService;
use App\Domains\RealEstate\Models\Property;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

final class RealEstateBlockchainVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RealEstateBlockchainVerificationService $service;
    private Tenant $tenant;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RealEstateBlockchainVerificationService::class);
        $this->tenant = Tenant::factory()->create();
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
        ]);
    }

    public function test_verify_document_on_blockchain_returns_valid_result(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $documentHash = hash('sha256', 'test_document');
        
        $result = $this->service->verifyDocumentOnBlockchain(
            $this->property->id,
            'title_deed',
            $documentHash,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_id', $result);
        $this->assertArrayHasKey('document_type', $result);
        $this->assertArrayHasKey('document_hash', $result);
        $this->assertArrayHasKey('verified', $result);
        $this->assertArrayHasKey('network', $result);
        $this->assertTrue($result['verified']);
        $this->assertEquals('ethereum', $result['network']);
    }

    public function test_verify_document_on_blockchain_caches_result(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $documentHash = hash('sha256', 'test_document');
        
        $firstCall = $this->service->verifyDocumentOnBlockchain(
            $this->property->id,
            'title_deed',
            $documentHash,
            1,
            $correlationId
        );

        $secondCall = $this->service->verifyDocumentOnBlockchain(
            $this->property->id,
            'title_deed',
            $documentHash,
            1,
            $correlationId
        );

        $this->assertEquals($firstCall, $secondCall);
    }

    public function test_verify_all_property_documents_verifies_all_types(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        
        $result = $this->service->verifyAllPropertyDocuments(
            $this->property->id,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_id', $result);
        $this->assertArrayHasKey('verification_results', $result);
        $this->assertArrayHasKey('all_verified', $result);
        $this->assertArrayHasKey('total_documents', $result);
        $this->assertArrayHasKey('verified_count', $result);
        $this->assertEquals(5, $result['total_documents']);
        $this->assertCount(5, $result['verification_results']);
    }

    public function test_verify_all_property_documents_marks_property_verified(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        
        $this->service->verifyAllPropertyDocuments(
            $this->property->id,
            1,
            $correlationId
        );

        $this->property->refresh();
        $this->assertTrue($this->property->metadata['blockchain_verified'] ?? false);
    }

    public function test_generate_smart_contract_returns_valid_contract(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $documentHashes = [
            hash('sha256', 'doc1'),
            hash('sha256', 'doc2'),
        ];
        
        $result = $this->service->generateSmartContract(
            $this->property->id,
            $documentHashes,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_id', $result);
        $this->assertArrayHasKey('contract_address', $result);
        $this->assertArrayHasKey('contract_abi_version', $result);
        $this->assertArrayHasKey('network', $result);
        $this->assertArrayHasKey('document_hashes', $result);
        $this->assertArrayHasKey('gas_used', $result);
        $this->assertArrayHasKey('transaction_hash', $result);
        $this->assertEquals('ethereum', $result['network']);
        $this->assertIsString($result['contract_address']);
    }

    public function test_generate_smart_contract_updates_property_metadata(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $documentHashes = [hash('sha256', 'doc1')];
        
        $this->service->generateSmartContract(
            $this->property->id,
            $documentHashes,
            1,
            $correlationId
        );

        $this->property->refresh();
        $this->assertTrue($this->property->metadata['smart_contract_deployed'] ?? false);
        $this->assertNotEmpty($this->property->metadata['smart_contract_address'] ?? '');
    }

    public function test_execute_contract_transaction_returns_valid_result(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $contractAddress = '0x' . str_repeat('0', 40);
        
        $result = $this->service->executeContractTransaction(
            $this->property->id,
            $contractAddress,
            'transferOwnership',
            ['newOwner' => '0x' . str_repeat('1', 40)],
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_id', $result);
        $this->assertArrayHasKey('contract_address', $result);
        $this->assertArrayHasKey('method_name', $result);
        $this->assertArrayHasKey('transaction_hash', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('transferOwnership', $result['method_name']);
        $this->assertEquals('success', $result['status']);
    }

    public function test_get_contract_state_returns_state_data(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $contractAddress = '0x' . str_repeat('0', 40);
        
        $result = $this->service->getContractState(
            $contractAddress,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('contract_address', $result);
        $this->assertArrayHasKey('network', $result);
        $this->assertArrayHasKey('block_number', $result);
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('last_updated', $result);
        $this->assertEquals('ethereum', $result['network']);
        $this->assertIsArray($result['state']);
    }

    public function test_get_contract_state_caches_result(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $contractAddress = '0x' . str_repeat('0', 40);
        
        $firstCall = $this->service->getContractState(
            $contractAddress,
            1,
            $correlationId
        );

        $secondCall = $this->service->getContractState(
            $contractAddress,
            1,
            $correlationId
        );

        $this->assertEquals($firstCall, $secondCall);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
