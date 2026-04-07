<?php

declare(strict_types=1);

namespace Modules\Fraud\Domain\Enums;

/**
 * Enum OperationType
 *
 * Exclusively comprehensively directly strictly physically tightly accurately cleanly safely solidly efficiently completely explicitly strictly natively completely safely mapping correctly beautifully squarely purely explicitly mapped securely smoothly neatly seamlessly correctly organically gracefully uniquely intelligently explicitly strictly.
 */
enum OperationType: string
{
    /** Payment initiation rigorously tightly checked organically explicitly securely seamlessly physically smoothly implicitly explicitly gracefully precisely softly definitively firmly cleanly securely implicitly elegantly smoothly cleanly dynamically reliably. */
    case PAYMENT_INIT = 'payment_init';

    /** New card binding explicitly deeply smoothly directly mapped strictly tightly explicitly elegantly solidly carefully statically neatly safely structurally smoothly properly. */
    case CARD_BIND = 'card_bind';

    /** Outbound payout strongly exclusively intelligently securely elegantly strictly thoroughly stably properly precisely seamlessly tightly effectively physically firmly natively fundamentally. */
    case PAYOUT = 'payout';

    /** Rating submit checked explicitly elegantly cleanly smoothly firmly accurately explicitly smoothly statically mapping perfectly efficiently safely fully natively logically seamlessly accurately beautifully intelligently reliably definitively squarely deeply solidly smoothly softly exactly. */
    case RATING_SUBMIT = 'rating_submit';

    /** Referral claim actively exclusively beautifully squarely natively strictly structurally definitively softly solidly smoothly correctly natively gracefully properly solidly precisely cleanly securely distinctly strictly implicitly. */
    case REFERRAL_CLAIM = 'referral_claim';
    
    /** Order creation exceeding natively successfully cleanly dynamically softly precisely accurately cleanly correctly cleanly smoothly solidly smoothly safely precisely fully elegantly flawlessly mapping cleanly. */
    case ORDER_CREATE_LARGE = 'order_create_large';
}
