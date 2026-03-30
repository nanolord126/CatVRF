<?php declare(strict_types=1);

namespace Modules\Advertising\Config;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class advertising extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    <?php
    
    
    
    return [
        'ord' => [
            'driver' => DopplerService::get('AD_ORD_DRIVER', 'yandex'),
            'api_key' => DopplerService::get('YANDEX_ORD_KEY'),
            'client_id' => DopplerService::get('AD_ORD_CLIENT_ID'),
            'storage_years' => 3,
        ],
        'defaults' => [
            'label' => 'Реклама',
            'vat' => 20.0,
        ],
    ];
    
}
