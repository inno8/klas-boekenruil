<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\SwapRequest;
use Illuminate\Http\Request;

class SwapController extends Controller
{
    public function requestSwap(Book $book)
    {
        if ($book->owner_id === auth()->id()) {
            abort(403, 'Je kunt geen ruilverzoek voor je eigen boek doen.');
        }

        $swap = SwapRequest::create([
            'book_id' => $book->id,
            'requester_id' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect('/my/swaps');
    }

    public function myRequests()
    {
        $outgoing = SwapRequest::with('book.owner')
            ->where('requester_id', auth()->id())
            ->get();

        $incoming = SwapRequest::with(['book', 'requester'])
            ->whereHas('book', function ($q) {
                $q->where('owner_id', auth()->id());
            })
            ->get();

        return view('swaps.index', [
            'outgoing' => $outgoing,
            'incoming' => $incoming,
        ]);
    }

    public function acceptSwap(SwapRequest $swap)
    {
        if ($swap->book->owner_id !== auth()->id()) {
            abort(403);
        }

        $swap->update(['status' => 'accepted']);

        return redirect('/my/swaps');
    }

    public function rejectSwap(SwapRequest $swap)
    {
        if ($swap->book->owner_id !== auth()->id()) {
            abort(403);
        }

        $swap->update(['status' => 'rejected']);

        return redirect('/my/swaps');
    }
}
