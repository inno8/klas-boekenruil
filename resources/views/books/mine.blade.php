<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Boekenruil — mijn boeken</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        .book { border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 8px; }
        .book h3 { margin: 0 0 0.5rem; }
        .book .meta { color: #666; font-size: 0.9rem; }
        .status { font-size: 0.85rem; font-weight: bold; }
        .status.beschikbaar { color: #28a745; }
        .status.geruild { color: #999; }
        .flash { background: #d4edda; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>Mijn boeken</h1>
    <p><a href="{{ route('books.index') }}">&larr; terug naar het aanbod</a></p>

    @if (session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif

    @forelse ($books as $book)
        <div class="book">
            <h3>{{ $book->title }}</h3>
            <div class="meta">
                door {{ $book->author }} · conditie: {{ $book->condition }}
                @if ($book->isbn)
                    · ISBN {{ $book->isbn }}
                @endif
            </div>
            <p class="status {{ $book->available ? 'beschikbaar' : 'geruild' }}">
                {{ $book->available ? 'Beschikbaar' : 'Geruild' }}
            </p>
            <form method="POST" action="{{ route('books.destroy', $book->id) }}" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit">Verwijderen</button>
            </form>
        </div>
    @empty
        <p>Je hebt nog geen boeken aangeboden.</p>
    @endforelse
</body>
</html>
