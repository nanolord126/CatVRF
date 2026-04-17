<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Compliance;

use App\Services\Compliance\ComplianceRequirementService;
use App\Services\Compliance\MdlpService;
use App\Services\Compliance\MercuryService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\LogManager;
use Tests\TestCase;

final class ComplianceServiceTest extends TestCase
{
    private ComplianceRequirementService $service;
    private MdlpService $mdlp;
    private MercuryService $mercury;
    private DatabaseManager $db;
    private AuditService $audit;
    private FraudControlService $fraud;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = app(DatabaseManager::class);
        $this->audit = app(AuditService::class);
        $this->fraud = app(FraudControlService::class);
        
        $this->service = new ComplianceRequirementService(
            $this->db,
            app(LogManager::class),
            $this->audit,
            $this->fraud,
        );

        $this->mdlp = new MdlpService(
            $this->db,
            app(LogManager::class),
            $this->audit,
        );

        $this->mercury = new MercuryService(
            $this->db,
            app(LogManager::class),
            $this->audit,
        );
    }

    public function test_record_compliance_requirement(): void
    {
        $recordId = $this->service->recordRequirement(
            tenantId: 1,
            requirementType: 'gdpr_consent',
            entityType: 'user',
            entityId: 1,
            status: 'compliant',
            evidence: json_encode(['consent_given' => true, 'timestamp' => now()]),
            correlationId: 'test-123',
        );

        $this->assertIsInt($recordId);
        $this->assertDatabaseHas('compliance_records', [
            'id' => $recordId,
            'tenant_id' => 1,
            'requirement_type' => 'gdpr_consent',
            'status' => 'compliant',
        ]);
    }

    public function test_check_compliance_status(): void
    {
        $this->service->recordRequirement(
            tenantId: 1,
            requirementType: 'gdpr_consent',
            entityType: 'user',
            entityId: 1,
            status: 'compliant',
            correlationId: 'test-123',
        );

        $status = $this->service->checkComplianceStatus(
            tenantId: 1,
            entityType: 'user',
            entityId: 1,
            requirementType: 'gdpr_consent',
            correlationId: 'test-123',
        );

        $this->assertEquals('compliant', $status);
    }

    public function test_get_non_compliant_entities(): void
    {
        $this->service->recordRequirement(
            tenantId: 1,
            requirementType: 'kyc_verification',
            entityType: 'user',
            entityId: 1,
            status: 'compliant',
            correlationId: 'test-123',
        );

        $this->service->recordRequirement(
            tenantId: 1,
            requirementType: 'kyc_verification',
            entityType: 'user',
            entityId: 2,
            status: 'non_compliant',
            correlationId: 'test-123',
        );

        $nonCompliant = $this->service->getNonCompliantEntities(
            tenantId: 1,
            requirementType: 'kyc_verification',
            correlationId: 'test-123',
        );

        $this->assertCount(1, $nonCompliant);
        $this->assertEquals(2, $nonCompliant[0]['entity_id']);
    }

    public function test_mdlp_send_document(): void
    {
        $documentId = $this->mdlp->sendDocument(
            tenantId: 1,
            documentType: 'waybill',
            documentData: [
                'sender_inn' => '1234567890',
                'receiver_inn' => '0987654321',
                'products' => [
                    ['name' => 'Product 1', 'quantity' => 10, 'price' => 1000],
                ],
            ],
            correlationId: 'test-123',
        );

        $this->assertIsInt($documentId);
        $this->assertDatabaseHas('compliance_records', [
            'id' => $documentId,
            'requirement_type' => 'mdlp_waybill',
            'status' => 'pending',
        ]);
    }

    public function test_mdlp_check_document_status(): void
    {
        $documentId = $this->mdlp->sendDocument(
            tenantId: 1,
            documentType: 'waybill',
            documentData: ['sender_inn' => '1234567890'],
            correlationId: 'test-123',
        );

        // Simulate status update
        $this->db->table('compliance_records')
            ->where('id', $documentId)
            ->update(['status' => 'accepted']);

        $status = $this->mdlp->checkDocumentStatus($documentId, 'test-123');

        $this->assertEquals('accepted', $status);
    }

    public function test_mercury_send_vet_certificate(): void
    {
        $certificateId = $this->mercury->sendVetCertificate(
            tenantId: 1,
            certificateData: [
                'product_type' => 'meat',
                'origin_country' => 'RU',
                'manufacturer' => 'Test Manufacturer',
                'batch_number' => 'BATCH-001',
            ],
            correlationId: 'test-123',
        );

        $this->assertIsInt($certificateId);
        $this->assertDatabaseHas('compliance_records', [
            'id' => $certificateId,
            'requirement_type' => 'mercury_vet_certificate',
            'status' => 'pending',
        ]);
    }

    public function test_mercury_check_certificate_status(): void
    {
        $certificateId = $this->mercury->sendVetCertificate(
            tenantId: 1,
            certificateData: ['product_type' => 'meat'],
            correlationId: 'test-123',
        );

        // Simulate status update
        $this->db->table('compliance_records')
            ->where('id', $certificateId)
            ->update(['status' => 'approved']);

        $status = $this->mercury->checkCertificateStatus($certificateId, 'test-123');

        $this->assertEquals('approved', $status);
    }

    public function test_get_compliance_report(): void
    {
        $this->service->recordRequirement(1, 'gdpr_consent', 'user', 1, 'compliant', null, 'test-123');
        $this->service->recordRequirement(1, 'gdpr_consent', 'user', 2, 'compliant', null, 'test-123');
        $this->service->recordRequirement(1, 'gdpr_consent', 'user', 3, 'non_compliant', null, 'test-123');

        $report = $this->service->getComplianceReport(
            tenantId: 1,
            startDate: now()->subDays(7),
            endDate: now(),
            correlationId: 'test-123',
        );

        $this->assertArrayHasKey('total_entities', $report);
        $this->assertArrayHasKey('compliant_count', $report);
        $this->assertArrayHasKey('non_compliant_count', $report);
        $this->assertArrayHasKey('compliance_rate', $report);
        $this->assertEquals(3, $report['total_entities']);
        $this->assertEquals(2, $report['compliant_count']);
        $this->assertEquals(1, $report['non_compliant_count']);
    }

    public function test_revoke_compliance(): void
    {
        $recordId = $this->service->recordRequirement(
            tenantId: 1,
            requirementType: 'gdpr_consent',
            entityType: 'user',
            entityId: 1,
            status: 'compliant',
            correlationId: 'test-123',
        );

        $result = $this->service->revokeCompliance(
            recordId: $recordId,
            reason: 'User revoked consent',
            correlationId: 'test-123',
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('compliance_records', [
            'id' => $recordId,
            'status' => 'revoked',
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->table('compliance_records')->truncate();
        parent::tearDown();
    }
}
