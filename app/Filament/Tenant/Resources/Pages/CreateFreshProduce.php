<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FreshProduce\Pages;

use use App\Filament\Tenant\Resources\FreshProduceResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFreshProduce extends CreateRecord
{
    protected static string $resource = FreshProduceResource::class;

    public function getTitle(): string
    {
        return 'Create FreshProduce';
    }
}