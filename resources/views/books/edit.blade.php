<!DOCTYPE html>
<html>
<head>
    <title>Boek bewerken</title>
</head>
<body>
    <h1>Boek bewerken</h1>

    <form method="POST" action="/books/{{ $book->id }}">
        @csrf
        @method('PUT')
        <p>
            <label>Titel</label><br>
            <input type="text" name="title" value="{{ $book->title }}">
        </p>
        <p>
            <label>Auteur</label><br>
            <input type="text" name="author" value="{{ $book->author }}">
        </p>
        <p>
            <label>ISBN</label><br>
            <input type="text" name="isbn" value="{{ $book->isbn }}">
        </p>
        <p>
            <label>Staat</label><br>
            <select name="condition">
                <option value="new" @if($book->condition == 'new') selected @endif>nieuw</option>
                <option value="good" @if($book->condition == 'good') selected @endif>goed</option>
                <option value="used" @if($book->condition == 'used') selected @endif>gebruikt</option>
            </select>
        </p>
        <button type="submit">opslaan</button>
    </form>
</body>
</html>
