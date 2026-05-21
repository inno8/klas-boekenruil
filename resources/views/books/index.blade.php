<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Boekenruil — aanbod</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        .book { border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; }
        .book h3 { margin: 0 0 0.5rem; }
        .book .meta { color: #666; font-size: 0.9rem; }
        .search { margin-bottom: 1.5rem; }
        .flash { background: #d4edda; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>Boekenruil — aanbod</h1>

    @if (session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif

    <form class="search" method="GET" action="{{ route('books.index') }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="Zoek op titel of auteur">
        <button type="submit">Zoeken</button>
    </form>

    @forelse ($books as $book)
        <div class="book">
            <h3>{{ $book->title }}</h3>
            <div class="meta">
                door {{ $book->author }} · conditie: {{ $book->condition }}
                @if ($book->isbn)
                    · ISBN {{ $book->isbn }}
                @endif
            </div>
            @if ($book->description)
                <p>{{ $book->description }}</p>
            @endif
            @auth
                <form method="POST" action="{{ route('books.destroy', $book->id) }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Verwijderen</button>
                </form>
            @endauth
        </div>
    @empty
        <p>Geen boeken gevonden.</p>
    @endforelse

    @auth
        <h2>Nieuw boek aanbieden</h2>
        <form method="POST" action="{{ route('books.store') }}">
            @csrf
            <p><label>Titel <input type="text" name="title" required></label></p>
            <p><label>Auteur <input type="text" name="author" required></label></p>
            <p><label>ISBN (optioneel) <input type="text" name="isbn"></label></p>
            <p><label>Conditie
                <select name="condition" required>
                    <option value="nieuw">nieuw</option>
                    <option value="goed" selected>goed</option>
                    <option value="gebruikt">gebruikt</option>
                    <option value="beschadigd">beschadigd</option>
                </select>
            </label></p>
            <p><label>Beschrijving<br><textarea name="description" rows="3"></textarea></label></p>
            <button type="submit">Aanbieden</button>
        </form>
    @else
        <p><a href="{{ url('/login') }}">Log in</a> om een boek aan te bieden.</p>
    @endauth
</body>
</html>
