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
        $actlogs = DB::table('all_activity_logs')->get();
        $foodstores = DB::table('food_stores')->get();
        $menu = DB::select('SELECT * FROM menu_items_and_food_stores');
        return Inertia::render('AdminInterface', [
            'users' => $users,
            'roles' => $roles,
            'actlogs' => $actlogs,
            'menu' => $menu,
            'foodstores' => $foodstores
        ]);
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

    public function deleteStore($storeId)
    {
        // Ensure the storeId is valid
        if (empty($storeId) || !is_numeric($storeId)) {
            return redirect()->back()->with('error', 'Invalid store ID.');
        }

        // Check if the store exists in the database
        $store = DB::table('food_stores')->where('id', $storeId)->first();

        if (!$store) {
            return redirect()->back()->with('error', 'Store not found.');
        }

        // Delete the store
        DB::table('food_stores')->where('id', $storeId)->delete();

        return redirect()->back()->with('message', 'User deleted successfully');
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

        return redirect()->back()->with('message', 'deleted successfully');
    }

    public function addStore(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'company' => 'required|string|max:255',
            'store_hours' => 'required|string|max:100',
        ]);

        // Insert data into the food_stores table
        DB::table('food_stores')->insert([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'company' => $validated['company'],
            'store_hours' => $validated['store_hours'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('message', 'added successfully');
    }

    public function refresh()
    {
        // Refresh the materialized view
        DB::statement('REFRESH MATERIALIZED VIEW all_activity_logs');

        return redirect()->back()->with('message', 'refreshed successfully');
    }


    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'menu_item_ids' => 'required|array',
            'menu_item_ids.*' => 'integer',
            'quantities' => 'required|array',
            'quantities.*' => 'integer',
            'remarks' => 'nullable|string',
            'payment_method' => 'required|string',
            'delivery_address' => 'required|string',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                DB::select('
                SELECT place_order_with_items(
                    :user_id, 
                    :menu_item_ids, 
                    :quantities, 
                    :remarks, 
                    :payment_method, 
                    :delivery_address
                )
            ', [
                    'user_id' => $validated['user_id'],
                    'menu_item_ids' => '{' . implode(',', $validated['menu_item_ids']) . '}',
                    'quantities' => '{' . implode(',', $validated['quantities']) . '}',
                    'remarks' => $validated['remarks'],
                    'payment_method' => $validated['payment_method'],
                    'delivery_address' => $validated['delivery_address'],
                ]);
            });

            return redirect()->back()->with('success', 'Order placed successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to place order: ' . $e->getMessage());
        }
    }
}
