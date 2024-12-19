<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserController extends Controller
{

    public function display_info()
    {
        $users = User::with('role')->get();
        $roles = DB::table('roles')->get();
        $books = DB::select('SELECT * FROM view_books');

        return Inertia::render('UserInterface', [
            'users' => $users,
            'roles' => $roles,
            'books' => $books
        ]);
    }

    public function search(Request $request)
    {
        $searchQuery = $request->input('searchQuery', '');

        if (empty($searchQuery)) {
            return Inertia::render('UserInterface', [
                'searchedbooks' => []
            ]);
        }

        try {
            $searchedbooks = DB::select('SELECT * FROM SearchBooksByTitle(?)', [$searchQuery]);
        } catch (\Exception $e) {
            return Inertia::render('UserInterface', [
                'searchedbooks' => [],
                'error' => $e->getMessage()
            ]);
        }

        return Inertia::render('UserInterface', [
            'searchedbooks' => $searchedbooks
        ]);
    }



    public function borrowBook(Request $request)
    {
        $request->validate([
            'users_id' => 'required|integer',
            'book_id' => 'required|integer',
        ]);

        $usersId = $request->input('users_id');
        $bookId = $request->input('book_id');

        try {
            DB::statement('SELECT insert_borrowed_book(?, ?)', [$usersId, $bookId]);

            return response()->json([
                'success' => true,
                'message' => 'Borrowed book successfully recorded.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }




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
