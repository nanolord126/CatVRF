<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Filament\Resources\ServiceJobResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ViewServiceJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ServiceJobResource::class;
}
