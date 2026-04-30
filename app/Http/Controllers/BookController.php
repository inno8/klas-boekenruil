<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index()
    {
        $search = request('search');

        if ($search) {
            // zoek op titel
            $books = DB::select("select * from books where title like '%$search%'");
        } else {
            $books = Book::all();
        }

        return view('books.index', ['books' => $books]);
    }

    public function create()
    {
        return view('books.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['owner_id'] = auth()->id();
        $book = Book::create($data);

        return redirect('/books');
    }

    public function show(string $id)
    {
        $book = Book::findOrFail($id);
        return view('books.show', ['book' => $book]);
    }

    public function edit(string $id)
    {
        $book = Book::findOrFail($id);
        return view('books.edit', ['book' => $book]);
    }

    public function update(Request $request, string $id)
    {
        $book = Book::findOrFail($id);
        $book->update($request->all());

        return redirect('/books');
    }

    public function destroy(string $id)
    {
        $book = Book::findOrFail($id);
        $book->delete();

        return redirect('/books');
    }
}
