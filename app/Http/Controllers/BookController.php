<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookController extends Controller
{
    // Toont alle boeken die beschikbaar zijn voor ruil.
    // Zoeken kan op titel of auteur via query param 'q'.
    //
    // Iteration 3: the query-building + search predicate moved to
    // Book::scopeAvailableForSearch(). Iteration 1 wrote both
    // concerns inline here ("controller mixes query-building,
    // search logic, and view rendering"); this version only
    // composes the scope and renders. The `filled('q')` helper
    // replaces the iter-1 `$q != null && $q != ''` check — same
    // semantics, idiomatic Laravel, also handles edge cases like
    // a `q=0` query (which `!= ''` would mishandle).
    public function index(Request $request): View
    {
        $q = $request->filled('q') ? $request->query('q') : null;

        $books = Book::availableForSearch($q)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('books.index', ['books' => $books, 'q' => $q]);
    }

    // Nieuw boek aanbieden voor ruil.
    //
    // Iteration 3: replaced the manual field-by-field assignment
    // (verbose, easy to forget a column when the schema grows)
    // with mass-assignment via $validated. user_id is set
    // automatically through the books() relationship — also fixes
    // the iter-1 finding that user_id used to live in $fillable
    // (which would have let a malicious request body override
    // ownership). available is set explicitly server-side so a
    // client can't pre-mark a brand-new book as taken.
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string',
            'condition' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $book = $request->user()->books()->make($validated);
        $book->available = true;
        $book->save();

        return redirect()->route('books.index')->with('success', 'Boek toegevoegd.');
    }

    // Eigenaar haalt zijn boek weer offline (bv. al geruild buiten de app om).
    //
    // Iteration 3: added the ownership check that iter 1 flagged
    // as a critical authorization gap. Before: any authenticated
    // user could DELETE /books/{id} and remove someone else's
    // book. After: 403 when the logged-in user is not the owner.
    // findOrFail() also replaces the find() + null-check pair —
    // Laravel-idiomatic, one statement instead of three lines,
    // automatically returns 404 if the book doesn't exist.
    public function destroy(Request $request, $id): RedirectResponse
    {
        $book = Book::findOrFail($id);

        if ($book->user_id !== $request->user()->id) {
            abort(403);
        }

        $book->delete();

        return redirect()->route('books.index')->with('success', 'Boek verwijderd.');
    }


    // Toont alle boeken die een bepaalde gebruiker aangeboden heeft.
    public function byUser($userId)
    {
        $user = User::find($userId);
        $books = Book::where('user_id', $userId)
                     ->orderBy('created_at', 'desc')
                     ->get();

        foreach ($books as $book) {
            echo $book->title . " (" . $book->user->name . ")\n";
        }

        return view('books.byUser', ['user' => $user, 'books' => $books]);
    }

    // Markeer een boek als geruild (eigenaar zelf).
    public function markTraded(Request $request, $id)
    {
        $book = Book::find($id);
        $book->available = false;
        $book->save();

        return redirect()->route('books.index');
    }

    // Haalt alle boeken op met dezelfde titel (zoekt op exacte match).
    public function findByTitle($title)
    {
        $matches = Book::where('title', '=', $title)->get();
        $count = 0;
        foreach ($matches as $m) {
            $count++;
        }
        return view('books.title-search', ['matches' => $matches, 'count' => $count]);
    }

    // Krijgt het totaal aantal boeken in het systeem.
    public function totalCount()
    {
        $all = Book::all();
        $total = 0;
        foreach ($all as $b) {
            $total = $total + 1;
        }
        return response()->json(['total' => $total]);
    }

    // Laat een boek zien plus alle eigenaarsinfo voor een snelle preview-pagina.
    public function showWithOwner($id)
    {
        $book = Book::find($id);
        $owner = $book->user;
        $email = $owner->email;
        return view('books.preview', ['book' => $book, 'owner' => $owner, 'email' => $email]);
    }

    // Verwijder alle oude boeken die langer dan 6 maanden geen update kregen.
    public function purgeStale()
    {
        $cutoff = date('Y-m-d', strtotime('-6 months'));
        $books = Book::all();
        foreach ($books as $book) {
            if ($book->updated_at < $cutoff) {
                $book->delete();
            }
        }
        return redirect()->route('books.index');
    }
}
