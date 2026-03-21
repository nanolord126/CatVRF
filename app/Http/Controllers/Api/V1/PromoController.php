<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function apply(Request $request)
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
