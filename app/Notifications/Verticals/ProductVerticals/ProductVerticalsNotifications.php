<?php declare(strict_types=1);

namespace App\Notifications\Verticals\ProductVerticals;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BasePushNotification;

// ========== COSMETICS ==========
final class CosmeticsOrderShippedNotification extends BaseMailableNotification
{
    protected string $type = 'cosmetics.order.shipped';
    protected string $template = 'emails.cosmetics.order_shipped';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your beauty order is on the way';
    }
}

final class CosmeticsArrivedNotification extends BasePushNotification
{
    protected string $type = 'cosmetics.delivery.arrived';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Your cosmetics have arrived!')
             ->body('Try out your new beauty products')
             ->type('success')
             ->deepLink('/orders/' . ($data['order_id'] ?? ''));
    }
}

// ========== JEWELRY ==========
final class JewelryOrderShippedNotification extends BaseMailableNotification
{
    protected string $type = 'jewelry.order.shipped';
    protected string $template = 'emails.jewelry.order_shipped';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your jewelry is on the way - Insured delivery';
    }
}

final class JewelryDeliveryConfirmedNotification extends BasePushNotification
{
    protected string $type = 'jewelry.delivery.confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Your jewelry has arrived!')
             ->body('Certificate: ' . ($data['certificate_number'] ?? ''))
             ->type('success')
             ->priority('high')
             ->autoClose(0);
    }
}

// ========== GIFTS ==========
final class GiftOrderShippedNotification extends BaseMailableNotification
{
    protected string $type = 'gifts.order.shipped';
    protected string $template = 'emails.gifts.order_shipped';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your gift is on the way!';
    }
}

final class GiftDeliveryArrivedNotification extends BasePushNotification
{
    protected string $type = 'gifts.delivery.arrived';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Gift delivered!')
             ->body('The gift for ' . ($data['recipient_name'] ?? '') . ' has arrived')
             ->type('success')
             ->deepLink('/gifts/' . ($data['gift_id'] ?? '') . '/track');
    }
}

// ========== FURNITURE ==========
final class FurnitureOrderConfirmedNotification extends BaseMailableNotification
{
    protected string $type = 'furniture.order.confirmed';
    protected string $template = 'emails.furniture.order_confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Furniture order confirmed - Delivery scheduled';
    }
}

final class FurnitureDeliveryScheduledNotification extends BasePushNotification
{
    protected string $type = 'furniture.delivery.scheduled';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Delivery scheduled')
             ->body('Your furniture will arrive ' . ($data['delivery_date'] ?? ''))
             ->type('info')
             ->deepLink('/orders/' . ($data['order_id'] ?? '') . '/track');
    }
}

// ========== ELECTRONICS ==========
final class ElectronicsOrderShippedNotification extends BaseMailableNotification
{
    protected string $type = 'electronics.order.shipped';
    protected string $template = 'emails.electronics.order_shipped';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Your gadget is on the way';
    }
}

final class ElectronicsDeliveryConfirmedNotification extends BasePushNotification
{
    protected string $type = 'electronics.delivery.confirmed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Device delivered!')
             ->body('Serial: ' . ($data['serial_number'] ?? ''))
             ->type('success')
             ->autoClose(8000)
             ->deepLink('/warranty/' . ($data['warranty_id'] ?? ''));
    }
}

// ========== BONUS NOTIFICATIONS ==========
namespace App\Notifications\Verticals\SpecialNotifications;

use App\Notifications\BaseMailableNotification;
use App\Notifications\BasePushNotification;

final class BonusEarnedNotification extends BasePushNotification
{
    protected string $type = 'bonus.earned';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Bonus earned!')
             ->body('₽' . number_format($data['bonus_amount'] ?? 0) . ' added to your balance')
             ->type('success')
             ->deepLink('/wallet');
    }
}

final class BonusExpiredNotification extends BasePushNotification
{
    protected string $type = 'bonus.expired';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Bonus expired')
             ->body('₽' . number_format($data['expired_amount'] ?? 0) . ' bonus has expired')
             ->type('warning')
             ->priority('high')
             ->deepLink('/wallet');
    }
}

final class BonusAboutToExpireNotification extends BasePushNotification
{
    protected string $type = 'bonus.about_to_expire';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Bonus expires soon!')
             ->body('₽' . number_format($data['bonus_amount'] ?? 0) . ' expires in ' . ($data['days_until_expiry'] ?? '0') . ' days')
             ->type('warning')
             ->priority('high')
             ->autoClose(0);
    }
}

// ========== WALLET NOTIFICATIONS ==========
final class WalletDepositReceivedNotification extends BaseMailableNotification
{
    protected string $type = 'wallet.deposit.received';
    protected string $template = 'emails.wallet.deposit_received';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Deposit received - ₽' . number_format($data['amount'] ?? 0);
    }
}

final class WalletWithdrawalProcessedNotification extends BaseMailableNotification
{
    protected string $type = 'wallet.withdrawal.processed';
    protected string $template = 'emails.wallet.withdrawal_processed';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'database']);
        $this->subject = 'Withdrawal processed - ₽' . number_format($data['amount'] ?? 0);
    }
}

final class WalletLowBalanceNotification extends BasePushNotification
{
    protected string $type = 'wallet.low_balance';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Low balance')
             ->body('Your balance is ₽' . number_format($data['current_balance'] ?? 0))
             ->type('warning')
             ->priority('high')
             ->deepLink('/wallet');
    }
}

final class WalletLimitReachedNotification extends BasePushNotification
{
    protected string $type = 'wallet.limit_reached';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Spending limit reached')
             ->body('Monthly limit of ₽' . number_format($data['limit_amount'] ?? 0) . ' reached')
             ->type('warning')
             ->priority('high');
    }
}

// ========== REFERRAL NOTIFICATIONS ==========
final class ReferralInviteSentNotification extends BasePushNotification
{
    protected string $type = 'referral.invite.sent';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Invite sent!')
             ->body('Share your referral code with friends')
             ->type('info')
             ->deepLink('/referral');
    }
}

final class ReferralFriendJoinedNotification extends BasePushNotification
{
    protected string $type = 'referral.friend.joined';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Friend joined!')
             ->body(($data['friend_name'] ?? 'A friend') . ' used your code')
             ->type('success')
             ->deepLink('/referral');
    }
}

final class ReferralBonusEarnedNotification extends BaseMailableNotification
{
    protected string $type = 'referral.bonus.earned';
    protected string $template = 'emails.referral.bonus_earned';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['mail', 'push', 'database']);
        $this->subject = 'Referral bonus earned - ₽' . number_format($data['bonus_amount'] ?? 0);
    }
}

final class ReferralMilestoneReachedNotification extends BasePushNotification
{
    protected string $type = 'referral.milestone.reached';

    public function __construct(int $userId, int $tenantId, array $data = [])
    {
        parent::__construct($userId, $tenantId, $data, channels: ['push', 'database']);
        
        $this->title('Milestone reached!')
             ->body('You invited ' . ($data['referral_count'] ?? '0') . ' friends!')
             ->type('success')
             ->deepLink('/referral');
    }
}
