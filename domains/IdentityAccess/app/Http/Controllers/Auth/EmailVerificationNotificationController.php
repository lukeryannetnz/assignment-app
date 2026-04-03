<?php

declare(strict_types=1);

namespace App\Domains\IdentityAccess\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);


        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('course-catalog.dashboard', absolute: false));
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
