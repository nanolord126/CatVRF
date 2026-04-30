# Technical Specification: Four-Eyes Approval Service
**CatVRF Healthcare Marketplace**
**Priority:** HIGH
**Complexity:** High
**Estimated Effort:** 3-4 weeks
**Date:** April 19, 2026

---

## 1. Overview

### 1.1 Problem Statement

Current system allows single users to execute critical operations without any oversight. This creates significant security and compliance risks:

- Single point of failure for critical operations
- Insider threat vulnerability (malicious employee)
- Regulatory non-compliance (financial regulations require dual control)
- No audit trail of approval decisions
- No conflict of interest detection

### 1.2 Solution

Implement **Four-Eyes Approval (Dual Control)** - require approval from a second authorized user before executing critical operations.

### 1.3 Key Features

1. **Critical Operation Classification:** Automatic detection of critical operations
2. **Approval Workflow:** Request → Review → Approve/Reject
3. **Time-Based Windows:** Approval must be completed within specified time window
4. **Conflict of Interest Detection:** Prevent self-approval and related-party approvals
5. **Approval Escalation:** Auto-escalate if no response within timeout
6. **Comprehensive Audit Trail:** Full audit of approval decisions in ClickHouse
7. **Filament Dashboard:** Management interface for approval queue

### 1.4 Success Criteria

- All critical operations require approval
- Self-approval prevented (100%)
- Conflict of interest detection rate > 95%
- Approval completion rate > 90%
- Average approval time < 30 minutes
- Zero false positives (non-critical operations not flagged)

---

## 2. Architecture

### 2.1 Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                      Four-Eyes Approval Layer                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │ OperationMonitor │───▶│CriticalOperation │                   │
│  │  (Middleware)    │    │  Classifier      │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                  │                               │
│                                  ▼                               │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │ ApprovalWorkflow │───▶│ ConflictDetector │                   │
│  │   (Orchestrator) │    │  (Self/Related)  │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                  │                               │
│                                  ▼                               │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │ ApprovalQueue    │───▶│ EscalationEngine │                   │
│  │  (Filament)      │    │  (Timeout)       │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                  │                               │
│                                  ▼                               │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │ ApprovalExecutor │───▶│   AuditLogger    │                   │
│  │  (Execute/Reject)│    │  (ClickHouse)    │                   │
│  └──────────────────┘    └──────────────────┘                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Data Flow

```
User Action → OperationMonitor → CriticalOperationClassifier
                                              │
                                              ▼
                                        Is Critical?
                                              │
                              ┌───────────────┴───────────────┐
                              ▼                               ▼
                              No                              Yes
                              │                               │
                              ▼                               ▼
                        Execute Direct               ApprovalWorkflow
                                                          │
                                                          ▼
                                                    ConflictDetector
                                                          │
                                          ┌───────────────┴───────────────┐
                                          ▼                               ▼
                                    Conflict Detected                  No Conflict
                                          │                               │
                                          ▼                               ▼
                                    Block/Reject                  Create ApprovalRequest
                                                                          │
                                                                          ▼
                                                                    ApprovalQueue
                                                                          │
                                          ┌───────────────────────────────┼───────────────────────────────┐
                                          ▼                               ▼                               ▼
                                    Approved                        Rejected                        Timeout
                                          │                               │                               │
                                          ▼                               ▼                               ▼
                                    Execute Operation            Log Rejection                   Escalate
                                    Log Approval                 Notify Requester                 Notify Manager
```

### 2.3 Integration Points

- **Existing:** FraudControlService, AuditService, TenantService
- **New:** FourEyesApprovalService, CriticalOperationClassifier, ConflictDetector, EscalationEngine
- **Middleware:** RequireApprovalMiddleware
- **Jobs:** ApprovalEscalationJob, ApprovalTimeoutJob
- **UI:** Filament ApprovalQueueResource
- **Storage:** Approval requests in MySQL, audit logs in ClickHouse

---

## 3. Detailed Specifications

### 3.1 Critical Operation Classification

**Critical Operations (requiring approval):**

| Operation Type | Threshold | Reason |
|----------------|-----------|--------|
| Wallet Withdrawal | >100,000 RUB | Large financial transaction |
| Wallet Withdrawal | >10,000 RUB (business) | Business transaction |
| Mass Product Edit | >100 products | Bulk data modification |
| User Role Change | Any (to admin/manager) | Privilege escalation |
| Tenant Settings Change | Critical settings | Tenant-wide impact |
| Payment Gateway Change | Any | Financial infrastructure |
| API Key Creation | Any | Security credential |
| Integration Enable/Disable | Any | Third-party access |
| Price Change (business) | >50% | Market manipulation |
| Order Cancellation (business) | >50,000 RUB | Large refund |

**Configuration:**

```php
// config/four_eyes.php
'critical_operations' => [
    'wallet_withdrawal' => [
        'enabled' => true,
        'thresholds' => [
            'individual' => 100000, // RUB
            'business' => 10000,
        ],
        'approval_window' => 3600, // 1 hour
        'approvers' => ['finance_manager', 'admin'],
    ],
    'mass_product_edit' => [
        'enabled' => true,
        'threshold' => 100, // products
        'approval_window' => 7200, // 2 hours
        'approvers' => ['catalog_manager', 'admin'],
    ],
    // ... more operations
],
```

### 3.2 FourEyesApprovalService

**Location:** `app/Services/Security/FourEyesApprovalService.php`

**Responsibilities:**
- Orchestrate approval workflow
- Create approval requests
- Check approval status
- Execute approved operations
- Reject operations

**Key Methods:**

```php
final readonly class FourEyesApprovalService
{
    public function __construct(
        private readonly CriticalOperationClassifier $classifier,
        private readonly ConflictDetector $conflictDetector,
        private readonly EscalationEngine $escalation,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Check if operation requires approval
     */
    public function requiresApproval(string $operationType, array $context): bool;

    /**
     * Create approval request
     */
    public function createApprovalRequest(
        string $operationType,
        int $requesterId,
        array $operationData,
        string $correlationId = ''
    ): ApprovalRequest;

    /**
     * Approve operation
     */
    public function approve(int $approvalRequestId, int $approverId, string $reason = ''): bool;

    /**
     * Reject operation
     */
    public function reject(int $approvalRequestId, int $approverId, string $reason): bool;

    /**
     * Execute approved operation
     */
    public function execute(int $approvalRequestId): bool;

    /**
     * Get approval status
     */
    public function getStatus(int $approvalRequestId): array;

    /**
     * Get pending approvals for user
     */
    public function getPendingApprovals(int $userId): array;

    /**
     * Escalate approval (timeout)
     */
    public function escalate(int $approvalRequestId): bool;
}
```

### 3.3 CriticalOperationClassifier

**Location:** `app/Services/Security/CriticalOperationClassifier.php`

**Responsibilities:**
- Classify operations as critical or not
- Apply thresholds based on operation type
- Determine required approval level

**Key Methods:**

```php
final readonly class CriticalOperationClassifier
{
    public function __construct() {}

    /**
     * Classify operation
     */
    public function classify(string $operationType, array $context): array;

    /**
     * Check if operation matches critical criteria
     */
    public function isCritical(string $operationType, array $context): bool;

    /**
     * Get required approval level
     */
    public function getApprovalLevel(string $operationType, array $context): string; // low, medium, high

    /**
     * Get approval window (seconds)
     */
    public function getApprovalWindow(string $operationType, array $context): int;

    /**
     * Get required approver roles
     */
    public function getRequiredApprovers(string $operationType, array $context): array;
}
```

### 3.4 ConflictDetector

**Location:** `app/Services/Security/ConflictDetector.php`

**Responsibilities:**
- Detect self-approval attempts
- Detect related-party approvals
- Detect conflict of interest

**Conflict Types:**

1. **Self-Approval:** Requester = Approver
2. **Same Team:** Requester and Approver in same team
3. **Reporting Line:** Approver reports to Requester
4. **Related Business:** Same business group
5. **Recent Collaboration:** Worked together in last 30 days

**Key Methods:**

```php
final readonly class ConflictDetector
{
    public function __construct() {}

    /**
     * Check for conflicts
     */
    public function hasConflict(int $requesterId, int $approverId): array;

    /**
     * Check for self-approval
     */
    public function isSelfApproval(int $requesterId, int $approverId): bool;

    /**
     * Check for same team
     */
    public function isSameTeam(int $requesterId, int $approverId): bool;

    /**
     * Check for reporting line conflict
     */
    public function isReportingLineConflict(int $requesterId, int $approverId): bool;

    /**
     * Check for related business conflict
     */
    public function isRelatedBusiness(int $requesterId, int $approverId): bool;

    /**
     * Get eligible approvers (excluding conflicts)
     */
    public function getEligibleApprovers(int $requesterId, array $requiredRoles): array;
}
```

### 3.5 EscalationEngine

**Location:** `app/Services/Security/EscalationEngine.php`

**Responsibilities:**
- Escalate approvals on timeout
- Notify managers
- Auto-approve for low-risk operations (optional)
- Auto-reject for high-risk operations

**Escalation Rules:**

| Approval Level | Timeout | Escalation Action |
|----------------|---------|-------------------|
| Low | 1 hour | Escalate to manager |
| Medium | 2 hours | Escalate to senior manager |
| High | 4 hours | Escalate to director + notify security |

**Key Methods:**

```php
final readonly class EscalationEngine
{
    public function __construct(
        private readonly NotificationService $notification,
    ) {}

    /**
     * Escalate approval
     */
    public function escalate(ApprovalRequest $request): bool;

    /**
     * Get escalation level
     */
    public function getEscalationLevel(ApprovalRequest $request): int; // 0, 1, 2, 3

    /**
     * Get escalation recipients
     */
    public function getEscalationRecipients(ApprovalRequest $request): array;

    /**
     * Send escalation notification
     */
    public function sendEscalationNotification(ApprovalRequest $request): void;
}
```

### 3.6 RequireApprovalMiddleware

**Location:** `app/Http/Middleware/RequireApprovalMiddleware.php`

**Responsibilities:**
- Intercept critical operations
- Check for existing approval
- Block operation if not approved
- Execute if approved

**Key Methods:**

```php
final class RequireApprovalMiddleware
{
    public function __construct(
        private readonly FourEyesApprovalService $approvalService,
        private readonly CriticalOperationClassifier $classifier,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $operationType = $this->getOperationType($request);
        $context = $this->getContext($request);

        // Check if operation requires approval
        if (!$this->classifier->isCritical($operationType, $context)) {
            return $next($request); // Not critical, proceed
        }

        // Check for existing approval
        $approvalId = $request->header('X-Approval-Id');
        if ($approvalId) {
            $status = $this->approvalService->getStatus((int) $approvalId);
            
            if ($status['status'] === 'approved') {
                // Execute approved operation
                return $this->executeApprovedOperation($request, $approvalId);
            } elseif ($status['status'] === 'rejected') {
                return $this->handleRejectedOperation($request, $status);
            }
        }

        // No approval, create request
        $approvalRequest = $this->approvalService->createApprovalRequest(
            $operationType,
            Auth::id(),
            $context,
            $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'error' => 'approval_required',
            'approval_request_id' => $approvalRequest->id,
            'message' => 'This operation requires approval',
        ], 403);
    }
}
```

---

## 4. Database Schema

### 4.1 approval_requests Table

**Migration:** `database/migrations/2026_04_19_000005_create_approval_requests_table.php`

```php
Schema::create('approval_requests', function (Blueprint $table) {
    $table->id();
    $table->string('request_id')->unique(); // UUID for external reference
    
    // Requester
    $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
    
    // Operation details
    $table->string('operation_type'); // wallet_withdrawal, mass_product_edit, etc.
    $table->json('operation_data'); // operation-specific data
    $table->string('approval_level'); // low, medium, high
    $table->integer('approval_window_seconds');
    
    // Approval status
    $table->string('status')->default('pending'); // pending, approved, rejected, expired, escalated
    $table->timestamp('approved_at')->nullable();
    $table->timestamp('rejected_at')->nullable();
    $table->timestamp('expired_at')->nullable();
    $table->timestamp('escalated_at')->nullable();
    
    // Approver
    $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
    $table->text('approval_reason')->nullable();
    $table->text('rejection_reason')->nullable();
    
    // Escalation
    $table->integer('escalation_level')->default(0);
    $table->foreignId('escalated_to_id')->nullable()->constrained('users')->onDelete('set null');
    
    // Execution
    $table->boolean('executed')->default(false);
    $table->timestamp('executed_at')->nullable();
    $table->json('execution_result')->nullable();
    
    // Conflict detection
    $table->json('conflicts_detected')->nullable();
    $table->boolean('has_conflicts')->default(false);
    
    // Metadata
    $table->string('correlation_id')->nullable();
    $table->ipAddress('requester_ip')->nullable();
    $table->string('requester_user_agent')->nullable();
    
    // Timestamps
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index('requester_id');
    $table->index('tenant_id');
    $table->index('business_group_id');
    $table->index('status');
    $table->index('operation_type');
    $table->index('approver_id');
    $table->index('expires_at');
    $table->index('created_at');
});
```

### 4.2 approval_actions Table

**Migration:** `database/migrations/2026_04_19_000006_create_approval_actions_table.php`

```php
Schema::create('approval_actions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    
    // Action details
    $table->string('action'); // created, viewed, approved, rejected, escalated, commented
    $table->text('comment')->nullable();
    $table->json('action_data')->nullable();
    
    // Metadata
    $table->ipAddress('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    
    // Timestamp
    $table->timestamp('created_at');
    
    // Indexes
    $table->index('approval_request_id');
    $table->index('user_id');
    $table->index('action');
    $table->index('created_at');
});
```

---

## 5. Configuration

**File:** `config/four_eyes.php`

```php
return [
    // Enable/disable four-eyes approval
    'enabled' => env('FOUR_EYES_ENABLED', true),
    
    // Critical operations configuration
    'critical_operations' => [
        'wallet_withdrawal' => [
            'enabled' => true,
            'thresholds' => [
                'individual' => 100000,
                'business' => 10000,
            ],
            'approval_level' => 'high',
            'approval_window' => 3600, // 1 hour
            'required_roles' => ['finance_manager', 'admin'],
        ],
        'mass_product_edit' => [
            'enabled' => true,
            'threshold' => 100,
            'approval_level' => 'medium',
            'approval_window' => 7200, // 2 hours
            'required_roles' => ['catalog_manager', 'admin'],
        ],
        'user_role_change' => [
            'enabled' => true,
            'target_roles' => ['admin', 'manager', 'finance_manager'],
            'approval_level' => 'high',
            'approval_window' => 3600,
            'required_roles' => ['admin'],
        ],
        // ... more operations
    ],
    
    // Conflict detection
    'conflict_detection' => [
        'enabled' => true,
        'prevent_self_approval' => true,
        'prevent_same_team' => true,
        'prevent_reporting_line' => true,
        'prevent_related_business' => true,
    ],
    
    // Escalation
    'escalation' => [
        'enabled' => true,
        'levels' => [
            0 => ['timeout' => 3600, 'escalate_to' => 'manager'],
            1 => ['timeout' => 7200, 'escalate_to' => 'senior_manager'],
            2 => ['timeout' => 14400, 'escalate_to' => 'director'],
        ],
        'auto_approve_low_risk' => false,
        'auto_reject_high_risk' => true,
    ],
    
    // Notifications
    'notifications' => [
        'channels' => ['database', 'mail', 'slack'],
        'slack_webhook' => env('SLACK_APPROVAL_WEBHOOK'),
    ],
    
    // Approval queue
    'queue' => [
        'auto_assign' => true,
        'load_balancing' => true,
        'max_approvals_per_user' => 10,
    ],
];
```

---

## 6. API Endpoints

**File:** `routes/api/four-eyes.php`

```php
Route::middleware(['auth:sanctum'])->prefix('v1/approvals')->group(function () {
    // Get my pending approvals
    Route::get('/pending', [ApprovalController::class, 'getPendingApprovals']);
    
    // Get my approval requests
    Route::get('/my-requests', [ApprovalController::class, 'getMyRequests']);
    
    // Get approval request details
    Route::get('/{approvalRequest}', [ApprovalController::class, 'show']);
    
    // Approve request
    Route::post('/{approvalRequest}/approve', [ApprovalController::class, 'approve']);
    
    // Reject request
    Route::post('/{approvalRequest}/reject', [ApprovalController::class, 'reject']);
    
    // Comment on request
    Route::post('/{approvalRequest}/comment', [ApprovalController::class, 'comment']);
    
    // Get eligible approvers
    Route::get('/{approvalRequest}/eligible-approvers', [ApprovalController::class, 'getEligibleApprovers']);
    
    // Escalate request (manual)
    Route::post('/{approvalRequest}/escalate', [ApprovalController::class, 'escalate']);
});
```

---

## 7. Filament Resources

### 7.1 ApprovalQueueResource

**Location:** `app/Filament/Resources/ApprovalQueueResource.php`

**Features:**
- List pending approvals
- Filter by status, operation type, requester
- Approve/Reject actions
- View operation details
- View conflict detection results
- Comment on requests
- Escalate requests

### 7.2 ApprovalHistoryResource

**Location:** `app/Filament/Resources/ApprovalHistoryResource.php`

**Features:**
- View all approval history
- Filter by date, status, operation type
- Export to CSV
- Audit trail view

---

## 8. Testing

### 8.1 Unit Tests

**File:** `tests/Unit/Services/Security/FourEyesApprovalServiceTest.php`

```php
class FourEyesApprovalServiceTest extends TestCase
{
    public function test_requires_approval_for_critical_operation()
    {
        // Test classification of critical operations
    }

    public function test_creates_approval_request()
    {
        // Test approval request creation
    }

    public function test_approves_operation()
    {
        // Test approval flow
    }

    public function test_rejects_operation()
    {
        // Test rejection flow
    }

    public function test_executes_approved_operation()
    {
        // Test execution after approval
    }
}
```

### 8.2 Feature Tests

**File:** `tests/Feature/FourEyesApprovalTest.php`

```php
class FourEyesApprovalTest extends TestCase
{
    public function test_critical_operation_blocked_without_approval()
    {
        // Test middleware blocks critical operations
    }

    public function test_approved_operation_executes_successfully()
    {
        // Test approved operation execution
    }

    public function test_self_approval_prevented()
    {
        // Test self-approval prevention
    }

    public function test_conflict_detection_blocks_related_approval()
    {
        // Test conflict detection
    }

    public function test_escalation_on_timeout()
    {
        // Test escalation flow
    }
}
```

### 8.3 Chaos Tests

**File:** `tests/Chaos/FourEyesApprovalChaosTest.php`

```php
class FourEyesApprovalChaosTest extends TestCase
{
    public function test_approval_with_database_failure()
    {
        // Test graceful degradation
    }

    public function test_approval_with_notification_failure()
    {
        // Test fallback when notifications fail
    }

    public function test_concurrent_approval_requests()
    {
        // Test race conditions
    }
}
```

---

## 9. Monitoring & Observability

### 9.1 Prometheus Metrics

```php
// Metrics to export
- four_eyes_approval_requests_total
- four_eyes_approvals_approved_total
- four_eyes_approvals_rejected_total
- four_eyes_approvals_expired_total
- four_eyes_approvals_escalated_total
- four_eyes_approval_duration_seconds
- four_eyes_pending_approvements_count
- four_eyes_conflicts_detected_total
```

### 9.2 Grafana Dashboard

Create dashboard with panels:
- Pending approvals by operation type
- Approval rate (approved/rejected)
- Average approval time
- Escalation rate
- Conflict detection rate
- Approval queue backlog

### 9.3 Alerts

- Alert if pending approvals > 50
- Alert if approval time > 1 hour average
- Alert if rejection rate > 20%
- Alert if escalation rate > 10%

---

## 10. Security Considerations

### 10.1 Fraud Control

- All approval operations pass through FraudControlService
- Rate limiting on approval attempts
- IP-based approval throttling

### 10.2 Audit Logging

- All approval events logged to ClickHouse
- Approval decisions with reasons
- Conflict detection results
- Correlation ID for all operations

### 10.3 Authorization

- Only users with required roles can approve
- Role-based access control (RBAC)
- Tenant isolation

### 10.4 Data Protection

- Sensitive operation data encrypted at rest
- Approval reasons audited
- PII handling compliance

---

## 11. Rollout Plan

### Phase 1: Shadow Mode (Week 1)
- Deploy in shadow mode (no enforcement)
- Collect data on critical operations
- Tune thresholds
- Train approvers

### Phase 2: Pilot (Week 2)
- Enable for finance operations only
- Monitor approval rate
- Adjust workflow
- Collect user feedback

### Phase 3: Gradual Rollout (Week 3)
- Enable for all critical operations
- Monitor performance
- Adjust conflict detection
- Optimize notification flow

### Phase 4: Full Rollout (Week 4)
- Enable enforcement for all operations
- Continuous monitoring
- Documentation and training
- Process refinement

---

## 12. Success Metrics

- 100% of critical operations require approval
- 0% self-approval rate
- >95% conflict detection rate
- >90% approval completion rate
- <30 minutes average approval time
- <5% escalation rate
- User satisfaction > 4.0/5

---

## 13. Dependencies

**Existing Services:**
- FraudControlService
- AuditService
- NotificationService
- TenantService
- WalletService

**Infrastructure:**
- MySQL (approval requests)
- ClickHouse (audit logs)
- Redis (caching)
- Slack/Email (notifications)

**External APIs:**
- Slack Webhook (optional)

---

## 14. Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Bottleneck in approval queue | Medium | High | Auto-assignment, load balancing |
| User resistance to new process | High | Medium | Training, clear UX, pilot phase |
| Conflict detection false positives | Medium | Medium | Tuning, whitelist exceptions |
| Approval timeout | Medium | Medium | Escalation, auto-approve for low-risk |
| Notification delivery failure | Low | Medium | Multiple channels, retry logic |

---

## 15. Open Questions

1. **Approval Thresholds:** Are the current thresholds appropriate for all business sizes?
2. **Auto-Approval:** Should we enable auto-approval for low-risk operations after timeout?
3. **Delegation:** Can approvers delegate approval authority temporarily?
4. **Batch Approval:** Should we support batch approval of similar operations?
5. **Mobile Approval:** Should we prioritize mobile approval experience?

---

## 16. Appendix: Operation Classification Examples

```php
// Example: Wallet Withdrawal
$context = [
    'amount' => 150000,
    'currency' => 'RUB',
    'user_type' => 'individual',
    'destination' => 'bank_account',
];

$result = $classifier->classify('wallet_withdrawal', $context);
// Returns: ['is_critical' => true, 'level' => 'high', 'window' => 3600]

// Example: Mass Product Edit
$context = [
    'product_count' => 150,
    'edit_type' => 'price_change',
    'percentage_change' => 20,
];

$result = $classifier->classify('mass_product_edit', $context);
// Returns: ['is_critical' => true, 'level' => 'medium', 'window' => 7200]

// Example: User Role Change
$context = [
    'target_user_id' => 123,
    'current_role' => 'user',
    'new_role' => 'admin',
];

$result = $classifier->classify('user_role_change', $context);
// Returns: ['is_critical' => true, 'level' => 'high', 'window' => 3600]
```

---

**Document Status:** Draft
**Next Review:** April 26, 2026
**Approved By:** [Pending]
