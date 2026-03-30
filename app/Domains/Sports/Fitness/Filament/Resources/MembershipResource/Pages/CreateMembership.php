<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Filament\Resources\MembershipResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateMembership extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = MembershipResource::class;
}
