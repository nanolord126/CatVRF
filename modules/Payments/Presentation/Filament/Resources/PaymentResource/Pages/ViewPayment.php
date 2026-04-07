<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Filament\Resources\PaymentResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Payments\Presentation\Filament\Resources\PaymentResource;

final class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;
}
