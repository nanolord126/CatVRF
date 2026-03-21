<?php namespace App\Models\Domains\Education; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Factories\HasFactory; class Course extends Model { use HasFactory; protected $guarded = []; protected static function newFactory() { return \Database\Factories\CourseFactory::new(); } 
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
