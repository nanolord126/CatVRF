<?php

declare(strict_types=1);

namespace Modules\Finances\Enums;

enum WalletType: string
{
    case TENANT = 'tenant';
    case BUSINESS_GROUP = 'business_group';
}
