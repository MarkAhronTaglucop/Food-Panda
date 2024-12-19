<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LibrarianController extends Controller
{
    public function display_info()
    {
        $users = User::with('role')->get();
        $roles = DB::table('roles')->get();
        $books = DB::select('SELECT * FROM view_books');

        return Inertia::render('LibrarianInterface', [
            'users' => $users,
            'roles' => $roles,
            'books' => $books
        ]);
    }

    public function search(Request $request)
    {
        $searchQuery = $request->input('searchQuery', '');

        if (empty($searchQuery)) {
            return Inertia::render('LibrarianInterface', [
                'searchedbooks' => []
            ]);
        }

        try {
            $searchedbooks = DB::select('SELECT * FROM SearchBooksByTitle(?)', [$searchQuery]);
        } catch (\Exception $e) {
            return Inertia::render('LibrarianInterface', [
                'searchedbooks' => [],
                'error' => $e->getMessage()
            ]);
        }

        return Inertia::render('LibrarianInterface', [
            'searchedbooks' => $searchedbooks
        ]);
    }

    public function updateBook(Request $request, $id)
    {
        try {
            // Validate the incoming request data
            $request->validate([
                'title' => 'required|string|max:255',
                'category' => 'required|string|max:50',
                'genre' => 'required|string|max:50',
                'year_published' => 'required|date',
                'number_of_copies' => 'required|integer|min:1',
            ]);

            // Execute the SQL function
            DB::statement('SELECT update_book_and_copies(?, ?, ?, ?, ?, ?)', [
                $id,
                $request->input('title'),
                $request->input('category'),
                $request->input('genre'),
                $request->input('year_published'),
                $request->input('number_of_copies'),
            ]);

            // Return success response
            return back()->with('success', 'Book updated successfully.');
        } catch (\Exception $e) {
            // Handle errors
            return back()->with('error', 'Failed to update the book: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::statement('SELECT delete_book_and_unused_authors(?)', [$id]);   

            return redirect()->route('librarian-dashboard')->with('success', 'Book and related records deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete book: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
{
    try {
        // Validate the incoming request data
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'genre' => 'required|string|max:50',
            'year_published' => 'required|date|before_or_equal:today',
            'author_name' => 'required|string|max:255',
            'author_country' => 'required|string|max:50',
        ]);

        // Execute the SQL function
        $result = DB::select('SELECT add_book_with_author_and_copy(?, ?, ?, ?, ?, ?)', [
            $request->input('title'),
            $request->input('category'),
            $request->input('genre'),
            $request->input('year_published'),
            $request->input('author_name'),
            $request->input('author_country'),
        ]);

        // Retrieve the message from the function's response
        

        // Return success response
        return back()->with('success', 'Book updated successfully.');
    } catch (\Exception $e) {
        // Handle errors (consider logging the exception)
        return redirect()->back()->with('error', 'Failed to add the book: ' . $e->getMessage());
    }
}

}
