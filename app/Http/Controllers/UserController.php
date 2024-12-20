<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;


class UserController extends Controller
{

    public function display_info()
    {
        $users = User::with('role')->get();
        $roles = DB::table('roles')->get();
        $menu = DB::select('SELECT * FROM menu_items_and_food_stores');
        $allorders = DB::select('SELECT * FROM all_orders');


        return Inertia::render('UserInterface', [
            'users' => $users,
            'roles' => $roles,
            'menu' => $menu,
            'allorders' => $allorders


        ]);
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
            Log::info('Validated Data:', $validated);

            DB::enableQueryLog();
            DB::transaction(function () use ($validated) {
                DB::statement('
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
                    'remarks' => $validated['remarks'] ?? null,
                    'payment_method' => $validated['payment_method'],
                    'delivery_address' => $validated['delivery_address'],
                ]);
            });

            Log::info('Executed Query:', DB::getQueryLog());
            return redirect()->back()->with('success', 'Order placed successfully!');
        } catch (\Exception $e) {
            Log::error('Order placement failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Failed to place order. Please try again.');
        }
    }

}
