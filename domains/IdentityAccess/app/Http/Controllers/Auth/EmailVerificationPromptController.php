<?php

declare(strict_types=1);

namespace App\Domains\IdentityAccess\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();
        assert($user !== null);


        return $user->hasVerifiedEmail()
                    ? redirect()->intended(route('course-catalog.dashboard', absolute: false))
                    : view('identity-access::auth.verify-email');
    }
}
