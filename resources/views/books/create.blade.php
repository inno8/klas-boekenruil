<!DOCTYPE html>
<html>
<head>
    <title>Nieuw boek</title>
</head>
<body>
    <h1>Boek toevoegen</h1>

    <form method="POST" action="/books">
        @csrf
        <p>
            <label>Titel</label><br>
            <input type="text" name="title">
        </p>
        <p>
            <label>Auteur</label><br>
            <input type="text" name="author">
        </p>
        <p>
            <label>ISBN</label><br>
            <input type="text" name="isbn">
        </p>
        <p>
            <label>Staat</label><br>
            <select name="condition">
                <option value="new">nieuw</option>
                <option value="good">goed</option>
                <option value="used">gebruikt</option>
            </select>
        </p>
        <button type="submit">opslaan</button>
    </form>
</body>
</html>
