<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;

final class FashionBrandFitProfile extends Model
{
    protected $table = 'fashion_brand_fit_profiles';
    protected $fillable = ['tenant_id', 'brand', 'runs_small', 'runs_large', 'true_to_size', 'correlation_id'];
    protected $casts = ['runs_small' => 'decimal:2', 'runs_large' => 'decimal:2', 'true_to_size' => 'decimal:2'];
}
