<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{

    public function users()
    {
        $users = User::with('role')->get();
        $roles = DB::table('roles')->get();
        return Inertia::render('AdminInterface', ['users' => $users, 'roles' => $roles]);
    }

    public function updateUserRole(Request $request, $userId)
    {
        // Fetch user from the database
        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            return back()->withErrors(['message' => 'User not found.']);
        }

        // Prevent changes for admin roles
        if ($user->role_id == 3) {
            return back()->withErrors(['message' => 'Admin roles cannot be edited or demoted.']);
        }

        // Validate the role_id
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id', // Ensure the role_id exists in the roles table
        ]);

        // Prevent demotion of an Admin
        if ($validated['role_id'] != 3 && $user->role_id == 3) {
            return back()->withErrors(['message' => 'Admins cannot be demoted.']);
        }

        // Update the user's role using DB facade
        DB::table('users')->where('id', $userId)->update([
            'role_id' => $validated['role_id'],
        ]);

        return back()->with('message', 'User role updated successfully!');
    }


    public function deleteUser(User $user)
    {
        // Ensure you're working with the user ID, not the entire user object
        $userId = is_object($user) ? $user->id : $user;

        // Ensure the userId is valid
        if (empty($userId) || !is_numeric($userId)) {
            return redirect()->back()->with('error', 'Invalid user ID');
        }

        // Check if the user is an admin
        $userRecord = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $userId)
            ->first();

        if ($userRecord && $userRecord->user_type === 'admin') {
            return redirect()->back()->with('error', 'Admin users cannot be deleted.');
        }

        // If the user is not an admin, delete the user
        DB::table('users')->where('id', $userId)->delete();

        return redirect()->back()->with('message', 'User deleted successfully');
    }
}
