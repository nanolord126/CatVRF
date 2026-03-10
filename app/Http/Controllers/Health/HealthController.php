<?php

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

class HealthController extends HealthCheckJsonResultsController
{
    // Inherits JSON results for programmatic health monitoring
}
