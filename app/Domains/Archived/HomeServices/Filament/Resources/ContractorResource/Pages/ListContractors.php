<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Filament\Resources\ContractorResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListContractors extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ContractorResource::class;


        protected function getHeaderActions(): array


        {


            return [\Filament\Actions\CreateAction::make()];


        }
}
