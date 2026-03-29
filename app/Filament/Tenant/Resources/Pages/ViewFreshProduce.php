<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FreshProduce\Pages;

use use App\Filament\Tenant\Resources\FreshProduceResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewFreshProduce extends ViewRecord
{
    protected static string $resource = FreshProduceResource::class;

    public function getTitle(): string
    {
        return 'View FreshProduce';
    }
}