<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\SoftDeletes as IlluminateSoftDeletes;

trait SoftDeletes
{
    use IlluminateSoftDeletes;

    public function isDeleted(): bool
    {
        return $this->trashed();
    }

    public function restoreOrFail(): bool
    {
        return $this->restore();
    }
}
