<!DOCTYPE html>
<html>
<head>
    <title>Boeken</title>
</head>
<body>
    <h1>Alle boeken</h1>

    <form method="GET" action="/books">
        <input type="text" name="search" placeholder="zoek op titel..." value="{{ request('search') }}">
        <button type="submit">zoek</button>
    </form>

    <a href="/books/create">+ nieuw boek</a>

    <ul>
        @foreach ($books as $book)
            <li>
                <strong>{!! $book->title !!}</strong>
                door {{ $book->author }}
                ({{ $book->condition }})
                @if($book->isbn) - ISBN: {{ $book->isbn }} @endif
                <br>
                eigenaar: {{ $book->owner->name }} (klas {{ $book->owner->klas }})
                <br>
                <a href="/books/{{ $book->id }}/edit">edit</a>
                <form method="POST" action="/books/{{ $book->id }}" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit">verwijder</button>
                </form>
            </li>
        @endforeach
    </ul>
</body>
</html>
