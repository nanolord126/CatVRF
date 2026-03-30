<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageSubscription\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListRecordsBeverageSubscription extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BeverageSubscriptionResource::class;
}
