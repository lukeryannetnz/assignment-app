<?php

declare(strict_types=1);

namespace App\Domain\IdentityAccess\Http\Controllers;

use App\Domain\IdentityAccess\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\ArgumentOutOfRangeException;

class AdminController
{
    /**
     * Display a listing of all users.
     */
    public function index(): View
    {
        $currentUser = request()->user();
        if ($currentUser == null || $currentUser->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $users = User::where('tenant_id', $currentUser->tenant_id)
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('identity-access::users.index', compact('users'));
    }

    /**
     * Promote a user to admin.
     */
    public function promoteToAdmin(int $id): RedirectResponse
    {
        $currentUser = request()->user();
        if ($currentUser == null || $currentUser->tenant_id === null) {
            throw new ArgumentOutOfRangeException("Tenant user is required.");
        }

        $user = User::where('tenant_id', $currentUser->tenant_id)->findOrFail($id);

        $user->update(['is_admin' => true]);

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
        $user = User::where('tenant_id', $currentUser->tenant_id)->findOrFail($id);

        // Prevent demoting yourself
        if ($user->id === $currentUser->id) {
            return redirect()->route('identity-access.admin.users.index')
                ->with('error', 'You cannot demote yourself.');
        }

        $user->update(['is_admin' => false]);

        return redirect()->route('identity-access.admin.users.index')
            ->with('success', "User {$user->name} has been demoted from admin.");
    }
}
