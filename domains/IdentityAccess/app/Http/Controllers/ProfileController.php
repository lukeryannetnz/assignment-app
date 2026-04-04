<?php

declare(strict_types=1);

namespace App\Domains\IdentityAccess\Http\Controllers;

use App\Domains\IdentityAccess\Http\Requests\ProfileUpdateRequest;
use App\Domains\IdentityAccess\Services\IdentityAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController
{
    public function __construct(private readonly IdentityAccessService $identityAccessService)
    {
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('identity-access::profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $this->identityAccessService->updateProfile(
            (int) $user->id,
            $request->string('name')->toString(),
            $request->string('email')->toString(),
        );

        return Redirect::route('identity-access.profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        assert($user !== null);

        Auth::logout();

        if ($user->tenant_id === null) {
            throw new \RuntimeException('Tenant user is required.');
        }

        $this->identityAccessService->deleteUser($user->tenant_id, (int) $user->id);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
