<?php
namespace App\Models\Domains\Insurance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\InsurancePolicyFactory;

class InsurancePolicy extends Model
{
    use HasFactory;
    protected $table = "insurance_policies";
    protected $guarded = [];

    protected static function newFactory()
    {
        return InsurancePolicyFactory::new();
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

