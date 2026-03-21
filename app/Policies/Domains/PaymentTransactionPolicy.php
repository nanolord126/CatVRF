<?php declare(strict_types=1);

namespace App\Policies\Domains;

use App\Domains\Payments\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class PaymentTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Admins can do anything
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * View payment (merchant, customer, accountant, manager)
     */
    public function view(User $user, PaymentTransaction $payment): bool
    {
        // Tenant scoping
        if ($payment->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Merchant (business) can view their own payments
        if ($payment->merchant_id === $user->current_business_group_id && $user->hasRole(['business_owner', 'manager', 'accountant'])) {
            return true;
        }

        // Customer can view their own transactions
        if ($payment->user_id === $user->id) {
            return true;
        }

        // Accountant/manager can view all
        return $user->hasRole(['accountant', 'manager']);
    }

    /**
     * Create payment (customer, business through API)
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['customer', 'business_owner', 'manager']);
    }

    /**
     * Refund payment (merchant, accountant, manager, admin)
     */
    public function refund(User $user, PaymentTransaction $payment): bool
    {
        if ($payment->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Only captured/completed payments can be refunded
        if ($payment->status !== 'captured') {
            return false;
        }

        // Merchant can refund their own
        if ($payment->merchant_id === $user->current_business_group_id && $user->hasRole(['manager'])) {
            return true;
        }

        // Accountant/business_owner can refund
        return $user->hasRole(['accountant', 'business_owner']);
    }

    /**
     * Capture payment (merchant, accountant, manager - for held payments)
     */
    public function capture(User $user, PaymentTransaction $payment): bool
    {
        if ($payment->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Only held payments can be captured
        if ($payment->status !== 'authorized') {
            return false;
        }

        // Merchant
        if ($payment->merchant_id === $user->current_business_group_id && $user->hasRole(['manager', 'business_owner'])) {
            return true;
        }

        return $user->hasRole(['accountant', 'manager']);
    }

    /**
     * View payment details and gateway response
     */
    public function viewDetails(User $user, PaymentTransaction $payment): bool
    {
        if ($payment->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Merchant can view their own details
        if ($payment->merchant_id === $user->current_business_group_id && $user->hasRole(['manager', 'business_owner'])) {
            return true;
        }

        // Customer can view their own
        if ($payment->user_id === $user->id) {
            return true;
        }

        // Accountant/admin
        return $user->hasRole(['accountant', 'admin']);
    }

    /**
     * Download payment receipt/invoice
     */
    public function downloadReceipt(User $user, PaymentTransaction $payment): bool
    {
        if ($payment->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Merchant
        if ($payment->merchant_id === $user->current_business_group_id) {
            return $user->hasRole(['manager', 'business_owner', 'accountant']);
        }

        // Customer
        if ($payment->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Process payout/withdrawal
     */
    public function processPayout(User $user, PaymentTransaction $payment): bool
    {
        if ($payment->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Only pending payouts can be processed
        if ($payment->status !== 'pending_payout') {
            return false;
        }

        // Merchant (business_owner)
        if ($payment->merchant_id === $user->current_business_group_id && $user->hasRole(['business_owner'])) {
            return true;
        }

        // Accountant/admin
        return $user->hasRole(['accountant', 'admin']);
    }

    /**
     * Delete payment (admin only, for testing/cleanup)
     */
    public function delete(User $user, PaymentTransaction $payment): bool
    {
        if ($payment->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * View fraud check details
     */
    public function viewFraudDetails(User $user, PaymentTransaction $payment): bool
    {
        if ($payment->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Only accountant/manager/admin can view fraud scoring
        return $user->hasRole(['accountant', 'manager', 'admin']);
    }
}
