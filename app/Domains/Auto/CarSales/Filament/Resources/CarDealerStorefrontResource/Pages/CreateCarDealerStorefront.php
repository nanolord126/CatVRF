<?php declare(strict_types=1);

namespace App\Domains\Auto\CarSales\Filament\Resources\CarDealerStorefrontResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateCarDealerStorefront extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CarDealerStorefrontResource::class;
}
