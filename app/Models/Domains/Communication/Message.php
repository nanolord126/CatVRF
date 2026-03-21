<?php
namespace App\Models\Domains\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\MessageFactory;

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

