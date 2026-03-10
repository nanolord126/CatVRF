<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller {
    public function setup(Request $request) {
        $user = $request->user();
        if ($user->two_factor_secret) {
            return response()->json(['secret' => $user->two_factor_secret]);
        }
        $google2fa = new Google2FA();
        $user->update(['two_factor_secret' => $google2fa->generateSecretKey()]);
        return response()->json(['secret' => $user->two_factor_secret]);
    }
}
