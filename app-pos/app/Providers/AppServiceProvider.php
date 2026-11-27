<?php

namespace App\Providers;

use App\Models\Business;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $accessToken, bool $isValid): bool {
            if (!$isValid) {
                return false;
            }

            $tokenable = $accessToken->tokenable;
            if (!$tokenable) {
                return false;
            }

            if (isset($tokenable->is_active) && !$tokenable->is_active) {
                return false;
            }

            if (!empty($tokenable->business_id) && !Business::whereKey($tokenable->business_id)->value('is_active')) {
                return false;
            }

            // Enforce finite expiration on legacy non-cashier tokens created without expires_at
            if (($tokenable->role ?? null) !== 'cashier' && $accessToken->expires_at === null) {
                $ttlMinutes = (int) config('sanctum.non_cashier_expiration_minutes', 10080);
                if ($ttlMinutes > 0 && $accessToken->created_at && $accessToken->created_at->lte(now()->subMinutes($ttlMinutes))) {
                    return false;
                }
            }

            return true;
        });
    }
}
