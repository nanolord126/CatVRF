<?php

namespace App\Services;

use App\Models\B2BPartner;
use App\Models\B2BOrder;
use App\Models\B2BInvoice;
use App\Services\Common\Security\AIAnomalyDetector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

class B2BService
{
    protected AIAnomalyDetector $fraudDetector;

    public function __construct(AIAnomalyDetector $fraudDetector)
    {
        $this->fraudDetector = $fraudDetector;
    }

    /**
     * Creates a B2B order from a system origin (booking, appointment, etc.)
     */
    public function createOrder(Model $origin, B2BPartner $partner, float $baseAmount): B2BOrder
    {
        // Внедрение глубокой проверки фрода перед созданием заказа (B2B Supply Chain Security)
        $tenant = Tenancy::tenant();
        $riskScore = $this->fraudDetector->analyze($tenant, auth()->id(), 'b2b_order_create', [
            'amount' => $baseAmount,
            'partner_id' => $partner->id,
            'origin' => get_class($origin),
        ]);

        if ($riskScore >= 70) {
            throw new \Exception("B2B Order blocked by AI Fraud Control (Risk Score: $riskScore). Suspicious B2B activity detected.");
        }

        $contract = $partner->getActiveContract();
        $discount = $contract ? $contract->discount_percent : 0;
        $finalAmount = $baseAmount * (1 - ($discount / 100));
        
        $correlationId = (string) Str::uuid();

        $order = B2BOrder::create([
            'partner_id' => $partner->id,
            'contract_id' => $contract?->id,
            'origin_type' => get_class($origin),
            'origin_id' => $origin->id,
            'amount' => $finalAmount,
            'status' => 'pending',
            'correlation_id' => $correlationId,
        ]);

        return $order;
    }

    /**
     * Generates an invoice for the B2B order
     */
    public function generateInvoice(B2BOrder $order): B2BInvoice
    {
        $partner = $order->partner;
        $contract = $order->contract;
        $dueDays = $contract ? $contract->payment_terms_days : 0;

        return B2BInvoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
            'amount' => $order->amount,
            'due_date' => now()->addDays($dueDays),
            'status' => 'unpaid',
            'correlation_id' => $order->correlation_id,
        ]);
    }
}
