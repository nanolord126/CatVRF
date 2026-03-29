<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageSubscription\Pages;
use App\Filament\Tenant\Resources\BeverageSubscriptionResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBeverageSubscription extends ListRecords {
    protected static string $resource = BeverageSubscriptionResource::class;
}
