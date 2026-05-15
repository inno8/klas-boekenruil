<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookController extends Controller
{
    // Toont alle boeken die beschikbaar zijn voor ruil.
    // Zoeken kan op titel of auteur via query param 'q'.
    public function index(Request $request): View
    {
        $q = $request->query('q');

        $books = Book::where('available', true);

        if ($q != null && $q != '') {
            $books = $books->where(function ($query) use ($q) {
                $query->where('title', 'like', '%' . $q . '%')
                      ->orWhere('author', 'like', '%' . $q . '%');
            });
        }

        $books = $books->orderBy('created_at', 'desc')->get();

        return view('books.index', ['books' => $books, 'q' => $q]);
    }

    // Nieuw boek aanbieden voor ruil.
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string',
            'condition' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $book = new Book();
        $book->user_id = $request->user()->id;
        $book->title = $request->input('title');
        $book->author = $request->input('author');
        $book->isbn = $request->input('isbn');
        $book->condition = $request->input('condition');
        $book->description = $request->input('description');
        $book->available = true;
        $book->save();

        return redirect()->route('books.index')->with('success', 'Boek toegevoegd.');
    }

    // Eigenaar haalt zijn boek weer offline (bv. al geruild buiten de app om).
    public function destroy(Request $request, $id): RedirectResponse
    {
        $book = Book::find($id);

        if ($book == null) {
            abort(404);
        }

        $book->delete();

        return redirect()->route('books.index')->with('success', 'Boek verwijderd.');
    }
}
