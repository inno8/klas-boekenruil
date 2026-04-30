<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    public function index()
    {
        // gewoon snel ophalen
        $favorites = DB::table('favorites')
            ->where('user_id', auth()->id())
            ->get();

        return response()->json($favorites);
    }

    public function store(Request $request)
    {
        $bookId = $request->input('book_id');

        $favorite = Favorite::create([
            'user_id' => auth()->id(),
            'book_id' => $bookId,
        ] + $request->all());

        return response()->json($favorite, 201);
    }

    public function destroy($bookId)
    {
        // even checken of er een favorite is en weg ermee
        $favorite = Favorite::where('book_id', $bookId)->first();

        if ($favorite) {
            $favorite->delete();
        }

        return response()->json(['ok' => true]);
    }
}
