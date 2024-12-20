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

        return Inertia::render('UserInterface', [
            'users' => $users,
            'roles' => $roles,
            'menu' => $menu

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


    // public function search(Request $request)
    // {
    //     $searchQuery = $request->input('searchQuery', '');

    //     if (empty($searchQuery)) {
    //         return Inertia::render('UserInterface', [
    //             'searchedbooks' => []
    //         ]);
    //     }

    //     try {
    //         $searchedbooks = DB::select('SELECT * FROM SearchBooksByTitle(?)', [$searchQuery]);
    //     } catch (\Exception $e) {
    //         return Inertia::render('UserInterface', [
    //             'searchedbooks' => [],
    //             'error' => $e->getMessage()
    //         ]);
    //     }

    //     return Inertia::render('UserInterface', [
    //         'searchedbooks' => $searchedbooks
    //     ]);
    // }



    // public function borrowBook(Request $request)
    // {
    //     $request->validate([
    //         'users_id' => 'required|integer',
    //         'book_id' => 'required|integer',
    //     ]);

    //     $usersId = $request->input('users_id');
    //     $bookId = $request->input('book_id');

    //     try {
    //         DB::statement('SELECT insert_borrowed_book(?, ?)', [$usersId, $bookId]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Borrowed book successfully recorded.',
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 400);
    //     }
    // }




    // public function borrowLogs()
    // {
    //     // Fetch borrowed books from the view_borrowed_books
    //     $borrowLogs = DB::table('view_borrowed_books')->get();

    //     // Get the authenticated user
    //     $user = auth()->user();

    //     // Return data to the Vue component using Inertia
    //     return Inertia::render('UserInterface', [
    //         'borrowLogs' => $borrowLogs,
    //         'user' => $user,  // Pass the logged-in user instead of all users
    //     ]);
    // }

}
