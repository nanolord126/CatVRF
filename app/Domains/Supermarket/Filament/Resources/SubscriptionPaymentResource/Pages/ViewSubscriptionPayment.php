<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource\Pages;

use App\Domains\Supermarket\Filament\Resources\SubscriptionPaymentResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewSubscriptionPayment extends ViewRecord
{
    protected static string $resource = SubscriptionPaymentResource::class;
}
