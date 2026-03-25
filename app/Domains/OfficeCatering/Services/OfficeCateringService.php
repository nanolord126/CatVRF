<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Services;

use App\Domains\OfficeCatering\Models\CateringCompany;
use App\Domains\OfficeCatering\Models\CateringOrder;
use App\Domains\OfficeCatering\Models\CateringMenu;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class OfficeCateringService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly InventoryManagementService $inventory,
        private readonly WalletService $wallet,
    ) {}

    public function createOrder(int $companyId, int $menuId, array $data, string $correlationId = ""): CateringOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        if (RateLimiter::tooManyAttempts("catering:order:".auth()->id(), 10)) {
            throw new \RuntimeException("Too many orders", 429);
        }
        RateLimiter::hit("catering:order:".auth()->id(), 3600);

        return $this->db->transaction(function () use ($companyId, $menuId, $data, $correlationId) {
            $company = CateringCompany::findOrFail($companyId);
            $menu = CateringMenu::where('id', $menuId)->where('catering_company_id', $companyId)->firstOrFail();

            if ($data['person_count'] < $company->min_person_count || $data['person_count'] > $company->max_person_count) {
                $this->log->channel('audit')->warning('Catering order person count out of range', [
                    'company_id' => $companyId,
                    'person_count' => $data['person_count'],
                    'min' => $company->min_person_count,
                    'max' => $company->max_person_count,
                    'correlation_id' => $correlationId,
                ]);
                throw new \RuntimeException("Количество персон вне допустимого диапазона", 400);
            }

            $total = $menu->price_kopecks * $data['person_count'];

            $fraud = $this->fraud->check([
                'user_id' => auth()->id() ?? 0,
                'operation_type' => 'catering_order_create',
                'correlation_id' => $correlationId,
                'amount' => $total,
                'ip_address' => request()->ip(),
            ]);

            if ($fraud['decision'] === 'block') {
                $this->log->channel('audit')->error('Catering order blocked by fraud', [
                    'user_id' => auth()->id(),
                    'score' => $fraud['score'],
                    'correlation_id' => $correlationId,
                ]);
                throw new \RuntimeException("Security block", 403);
            }

            $order = CateringOrder::create([
                'uuid' => Str::uuid(),
                'tenant_id' => tenant()->id,
                'catering_company_id' => $companyId,
                'client_id' => auth()->id() ?? 0,
                'correlation_id' => $correlationId,
                'office_name' => $data['office_name'],
                'office_address' => $data['office_address'],
                'delivery_datetime' => $data['delivery_datetime'],
                'person_count' => $data['person_count'],
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'commission_kopecks' => (int) ($total * 0.14),
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'menu_items_json' => ['menu_id' => $menuId, 'menu_name' => $menu->name, 'person_count' => $data['person_count']],
                'special_requests' => $data['special_requests'] ?? null,
                'tags' => ['office_catering' => true, 'person_count' => $data['person_count'], 'delivery_date' => now()->toDateString()],
            ]);

            $this->log->channel('audit')->info('Catering order created', [
                'order_id' => $order->id,
                'company_id' => $companyId,
                'total_kopecks' => $total,
                'person_count' => $data['person_count'],
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function completeOrder(int $orderId, string $correlationId = ""): CateringOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId) {
            $order = CateringOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException("Заказ не оплачен", 400);
            }

            if ($order->status !== 'pending_payment') {
                throw new \RuntimeException("Заказ имеет некорректный статус", 400);
            }

            $order->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
                'tags' => array_merge($order->tags ?? [], ['completed_at' => now()->toIso8601String()]),
            ]);

            $company = $order->company;
            $payout = $order->payout_kopecks;

            $this->wallet->credit(tenant()->id, $payout, 'catering_payout', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
            ]);

            $this->log->channel('audit')->info('Catering order completed and payout credited', [
                'order_id' => $order->id,
                'company_id' => $company->id,
                'payout_kopecks' => $payout,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function cancelOrder(int $orderId, string $correlationId = ""): CateringOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($orderId, $correlationId) {
            $order = CateringOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException("Нельзя отменить завершённый заказ", 400);
            }

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
                'tags' => array_merge($order->tags ?? [], ['cancelled_at' => now()->toIso8601String()]),
            ]);

            if ($order->payment_status === 'completed') {
                $this->wallet->credit(tenant()->id, $order->total_kopecks, 'catering_refund', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                ]);
            }

            $this->log->channel('audit')->info('Catering order cancelled', [
                'order_id' => $order->id,
                'company_id' => $order->catering_company_id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function getOrder(int $orderId): CateringOrder
    {
        return CateringOrder::with(['company'])->findOrFail($orderId);
    }

    public function getUserOrders(int $clientId, int $limit = 10)
    {
        return CateringOrder::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function getCompanyOrders(int $companyId, int $limit = 20)
    {
        return CateringOrder::where('catering_company_id', $companyId)
            ->orderBy('delivery_datetime', 'desc')
            ->take($limit)
            ->get();
    }

    public function getMenus(int $companyId)
    {
        return CateringMenu::where('catering_company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
