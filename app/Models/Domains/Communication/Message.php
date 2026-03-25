declare(strict_types=1);

<?php
namespace App\Models\Domains\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\MessageFactory;

/**
 * Message
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Message extends Model
{
    use HasFactory;
    protected $table = "messages";
    protected $guarded = [];
    protected $casts = ["tags" => "json"];

    protected static function newFactory()
    {
        return MessageFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}

