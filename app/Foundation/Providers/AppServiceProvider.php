<?php

declare(strict_types=1);

namespace App\Foundation\Providers;

use App\Domain\Tenancy\Support\TenantContext;
use App\Domain\IdentityAccess\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, static fn (): TenantContext => new TenantContext());
    }

    public function boot(): void
    {
        Paginator::defaultView('foundation::vendor.pagination.tailwind');
        Paginator::defaultSimpleView('foundation::vendor.pagination.tailwind');

        VerifyEmail::createUrlUsing(static function (mixed $notifiable): string {
            if (!$notifiable instanceof User) {
                throw new \InvalidArgumentException('VerifyEmail notifiable must be a User.');
            }

            return URL::temporarySignedRoute(
                'identity-access.auth.verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );
        });

        ResetPassword::createUrlUsing(static function (mixed $notifiable, string $token): string {
            if (!$notifiable instanceof User) {
                throw new \InvalidArgumentException('ResetPassword notifiable must be a User.');
            }

            return route('identity-access.auth.password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], absolute: false);
        });
    }
}
