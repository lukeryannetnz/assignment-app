<?php

declare(strict_types=1);

namespace App\Domains\IdentityAccess\Http\Controllers\Auth;

use App\Domains\IdentityAccess\Services\IdentityAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController
{
    public function __construct(private readonly IdentityAccessService $identityAccessService)
    {
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $this->identityAccessService->updatePassword((int) $user->id, $validated['password']);

        return back()->with('status', 'password-updated');
    }
}
