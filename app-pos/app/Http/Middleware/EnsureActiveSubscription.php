<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Require an active business tenant with a valid subscribed workspace for operational POS & store endpoints.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Autentikasi diperlukan.',
            ], 401);
        }

        if ($user->role === 'superadmin') {
            return $next($request);
        }

        $business = $user->business;
        if (!$business || !$business->is_active) {
            return response()->json([
                'status' => 'error',
                'code' => 'BUSINESS_INACTIVE',
                'message' => 'Akun bisnis tidak aktif atau sedang dibekukan.',
            ], 403);
        }

        if (!$business->hasSubscribedAppAccess()) {
            return response()->json([
                'status' => 'error',
                'code' => 'SUBSCRIPTION_REQUIRED',
                'message' => 'Paket langganan belum aktif atau telah kedaluwarsa. Silakan aktifkan atau perpanjang paket di menu Langganan & Tagihan.',
            ], 403);
        }

        return $next($request);
    }
}
