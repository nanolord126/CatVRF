<?php namespace App\Models\Domains\RealEstate; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Factories\HasFactory; class Property extends Model { use HasFactory; protected $guarded = []; protected static function newFactory() { return \Database\Factories\PropertyFactory::new(); } 
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
