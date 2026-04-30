<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource\Pages;

use App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSubscriptionPayment extends CreateRecord
{
    protected static string $resource = SubscriptionPaymentResource::class;
}
