<?php

declare(strict_types=1);

namespace App\Domains\IdentityAccess\Http\Controllers;

use App\Domains\IdentityAccess\Services\IdentityAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class AdminController
{
    public function __construct(private readonly IdentityAccessService $identityAccessService)
    {
    }

    /**
     * Display a listing of all users.
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();
        if ($currentUser == null || $currentUser->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $users = $this->identityAccessService->paginateUsers($currentUser->tenant_id, 5, $request->integer('page', 1));

        return view('identity-access::users.index', compact('users'));
    }

    /**
     * Promote a user to admin.
     */
    public function promoteToAdmin(Request $request, int $id): RedirectResponse
    {
        $currentUser = $request->user();
        if ($currentUser == null || $currentUser->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $user = $this->identityAccessService->promoteUserToAdmin($currentUser->tenant_id, $id);

        return redirect()->route('identity-access.admin.users.index')
            ->with('success', "User {$user->name} has been promoted to admin.");
    }

    /**
     * Demote a user from admin.
     */
    public function demoteFromAdmin(Request $request, int $id): RedirectResponse
    {
        $currentUser = $request->user();
        if ($currentUser == null || $currentUser->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }
        $user = $this->identityAccessService->findUser($currentUser->tenant_id, $id);

        // Prevent demoting yourself
        if ($user->id === $currentUser->id) {
            return redirect()->route('identity-access.admin.users.index')
                ->with('error', 'You cannot demote yourself.');
        }

        $user = $this->identityAccessService->demoteUserFromAdmin($currentUser->tenant_id, $id);

        return redirect()->route('identity-access.admin.users.index')
            ->with('success', "User {$user->name} has been demoted from admin.");
    }
}
