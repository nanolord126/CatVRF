<?php declare(strict_types=1);

namespace App\Services;


use Illuminate\Http\Request;
use App\Models\BusinessGroup;
use App\Services\FraudControlService;
use App\Services\WalletService;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

/**
 * BusinessGroupService — управление юридическими лицами / филиалами.
 *
 * Правила канона:
 *  - Один Tenant может иметь несколько BusinessGroup (по разным ИНН)
 *  - При создании — автоматически создаётся Wallet для филиала
 *  - Переключение активного филиала — в сессии (active_business_group_id)
 *  - B2B-tier: standard → silver → gold → platinum (управляется вручную Admin)
 *  - Кредитный лимит списывается при B2B-заказах и пополняется при оплате
 *  - FraudControlService::check() перед финансовыми операциями
 *  - Все изменения логируются через $this->logger->channel('audit')
 */
final readonly class BusinessGroupService
{
    public function __construct(
        private readonly Request $request,
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    /**
     * Создать новый филиал / юр.лицо для tenant.
     */
    public function create(
        int     $tenantId,
        string  $name,
        string  $inn,
        string  $correlationId,
        ?string $legalName       = null,
        ?string $kpp             = null,
        ?string $legalAddress    = null,
        ?string $bankAccount     = null,
        ?string $bankName        = null,
        ?string $bic             = null,
    ): BusinessGroup {
        $this->fraud->check(
            (int) $this->guard->id(),
            'business_group_create',
            0,
            $this->request->ip(),
            null,
            $correlationId,
        );

        return $this->db->transaction(function () use (
            $tenantId, $name, $inn, $legalName, $kpp, $legalAddress,
            $bankAccount, $bankName, $bic, $correlationId
        ): BusinessGroup {
            $group = BusinessGroup::create([
                'tenant_id'      => $tenantId,
                'uuid'           => Str::uuid()->toString(),
                'name'           => $name,
                'legal_name'     => $legalName ?? $name,
                'inn'            => $inn,
                'kpp'            => $kpp,
                'legal_address'  => $legalAddress,
                'bank_account'   => $bankAccount,
                'bank_name'      => $bankName,
                'bic'            => $bic,
                'is_active'      => true,
                'correlation_id' => $correlationId,
            ]);

            // Создаём кошелёк для филиала
            $this->wallet->createWallet(
                tenantId:        $tenantId,
                businessGroupId: $group->id,
                userId:          null,
            );

            $this->logger->channel('audit')->info('BusinessGroup created', [
                'business_group_id' => $group->id,
                'tenant_id'         => $tenantId,
                'inn'               => $inn,
                'correlation_id'    => $correlationId,
            ]);

            return $group;
        });
    }

    /**
     * Переключить активный филиал в сессии пользователя.
     * Проверяет принадлежность group к tenant пользователя.
     */
    public function switchActive(int $groupId, int $tenantId, string $correlationId): BusinessGroup
    {
        $group = BusinessGroup::where('id', $groupId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->firstOrFail();

        session(['active_business_group_id' => $group->id]);

        $this->logger->channel('audit')->info('BusinessGroup switched', [
            'business_group_id' => $group->id,
            'tenant_id'         => $tenantId,
            'correlation_id'    => $correlationId,
        ]);

        return $group;
    }

    /**
     * Обновить B2B-tier (только Admin).
     */
    public function updateTier(BusinessGroup $group, string $tier, string $correlationId): void
    {
        $allowed = ['standard', 'silver', 'gold', 'platinum'];
        if (!in_array($tier, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid B2B tier: {$tier}");
        }

        $this->db->transaction(static function () use ($group, $tier, $correlationId): void {
            $group->update([
                'b2b_tier'       => $tier,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('BusinessGroup tier updated', [
                'business_group_id' => $group->id,
                'new_tier'          => $tier,
                'correlation_id'    => $correlationId,
            ]);
        });
    }

    /**
     * Установить кредитный лимит (только Admin или Tenant-owner).
     */
    public function setCreditLimit(BusinessGroup $group, int $limitKopecks, string $correlationId): void
    {
        $this->fraud->check(
            (int) $this->guard->id(),
            'business_group_credit_limit',
            $limitKopecks,
            $this->request->ip(),
            null,
            $correlationId,
        );

        $this->db->transaction(static function () use ($group, $limitKopecks, $correlationId): void {
            $group->update([
                'credit_limit_kopecks' => $limitKopecks,
                'correlation_id'       => $correlationId,
            ]);

            $this->logger->channel('audit')->info('BusinessGroup credit limit set', [
                'business_group_id'    => $group->id,
                'credit_limit_kopecks' => $limitKopecks,
                'correlation_id'       => $correlationId,
            ]);
        });
    }

    /**
     * Списать из кредитного лимита (при B2B-заказе с отсрочкой).
     * Вызывается из B2BOrderService.
     */
    public function consumeCredit(BusinessGroup $group, int $amountKopecks, string $correlationId): void
    {
        $this->db->transaction(static function () use ($group, $amountKopecks, $correlationId): void {
            // lockForUpdate для concurrency safety
            $fresh = BusinessGroup::lockForUpdate()->findOrFail($group->id);

            $available = $fresh->credit_limit_kopecks - $fresh->credit_used_kopecks;
            if ($available < $amountKopecks) {
                throw new \DomainException(
                    "Insufficient credit limit for BusinessGroup #{$group->id}: "
                    . "available={$available}, required={$amountKopecks}"
                );
            }

            $fresh->increment('credit_used_kopecks', $amountKopecks);

            $this->logger->channel('audit')->info('BusinessGroup credit consumed', [
                'business_group_id' => $group->id,
                'amount_kopecks'    => $amountKopecks,
                'used_after'        => $fresh->credit_used_kopecks + $amountKopecks,
                'correlation_id'    => $correlationId,
            ]);
        });
    }

    /**
     * Вернуть кредитный лимит (при оплате B2B-долга).
     * Вызывается из PaymentService при успешном webhook'е.
     */
    public function releaseCredit(BusinessGroup $group, int $amountKopecks, string $correlationId): void
    {
        $this->db->transaction(static function () use ($group, $amountKopecks, $correlationId): void {
            $fresh = BusinessGroup::lockForUpdate()->findOrFail($group->id);

            $newUsed = max(0, $fresh->credit_used_kopecks - $amountKopecks);
            $fresh->update(['credit_used_kopecks' => $newUsed]);

            $this->logger->channel('audit')->info('BusinessGroup credit released', [
                'business_group_id' => $group->id,
                'amount_kopecks'    => $amountKopecks,
                'used_after'        => $newUsed,
                'correlation_id'    => $correlationId,
            ]);
        });
    }

    /**
     * Деактивировать филиал.
     */
    public function deactivate(BusinessGroup $group, string $correlationId): void
    {
        $this->db->transaction(static function () use ($group, $correlationId): void {
            $group->update([
                'is_active'      => false,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('BusinessGroup deactivated', [
                'business_group_id' => $group->id,
                'correlation_id'    => $correlationId,
            ]);
        });
    }
}
