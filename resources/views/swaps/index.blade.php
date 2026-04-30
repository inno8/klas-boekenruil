<!DOCTYPE html>
<html>
<head>
    <title>Mijn ruilverzoeken</title>
</head>
<body>
    <h1>Mijn ruilverzoeken</h1>

    <h2>Uitgaand</h2>
    <ul>
        @foreach ($outgoing as $swap)
            <li>
                {{ $swap->book->title }} ({{ $swap->book->owner->name }}) — <em>{{ $swap->status }}</em>
            </li>
        @endforeach
    </ul>

    <h2>Inkomend</h2>
    <ul>
        @foreach ($incoming as $swap)
            <li>
                {{ $swap->requester->name }} wil {{ $swap->book->title }} — <em>{{ $swap->status }}</em>
                @if ($swap->status === 'pending')
                    <form method="POST" action="/swaps/{{ $swap->id }}/accept" style="display:inline">
                        @csrf
                        <button type="submit">accepteer</button>
                    </form>
                    <form method="POST" action="/swaps/{{ $swap->id }}/reject" style="display:inline">
                        @csrf
                        <button type="submit">weiger</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
</body>
</html>
