<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;

class BookController extends Controller
{
    public function index()
    {
        $search = request('search');

        $query = Book::query();
        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        $books = $query->get();

        return view('books.index', ['books' => $books]);
    }

    public function create()
    {
        return view('books.create');
    }

    public function store(StoreBookRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = auth()->id();
        Book::create($data);

        return redirect('/books');
    }

    public function show(Book $book)
    {
        return view('books.show', ['book' => $book]);
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);
        return view('books.edit', ['book' => $book]);
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update', $book);
        $book->update($request->validated());

        return redirect('/books');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);
        $book->delete();

        return redirect('/books');
    }
}
