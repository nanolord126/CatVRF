<?php

declare(strict_types=1);

namespace Modules\Payments\Presentation\Filament\Resources\PaymentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Payments\Presentation\Filament\Resources\PaymentResource;

final class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
}
