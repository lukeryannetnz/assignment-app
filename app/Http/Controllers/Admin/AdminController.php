<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $users = User::orderBy('created_at', 'desc')->paginate(5);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Promote a user to admin.
     */
    public function promoteToAdmin(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $user->update(['is_admin' => true]);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} has been promoted to admin.");
    }

    /**
     * Demote a user from admin.
     */
    public function demoteFromAdmin(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $currentUser = $request->user();
        if ($currentUser == null) {
            throw new ArgumentOutOfRangeException("User is required.");
        }

        // Prevent demoting yourself
        if ($user->id === $currentUser->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot demote yourself.');
        }

        $user->update(['is_admin' => false]);

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} has been demoted from admin.");
    }
}
