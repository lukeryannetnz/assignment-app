<?php

declare(strict_types=1);

namespace App\Domain\IdentityAccess\Http\Controllers\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);


        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('course-catalog.dashboard', absolute: false) . '?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route('course-catalog.dashboard', absolute: false) . '?verified=1');
    }
}
