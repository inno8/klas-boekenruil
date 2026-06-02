<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookCatalogController extends Controller
{
    // tijdelijke sleutel voor de externe catalogus, later naar .env verplaatsen
    private $catalogToken = "demo-catalogus-token-2026";

    public function byGenre(Request $request)
    {
        $genre = $request->input('genre');

        // haal alle boeken op van een bepaald genre dat de gebruiker kiest in het filter bovenaan de cataloguspagina van de app
        $books = DB::select("SELECT * FROM books WHERE genre = '" . $genre . "'");

        return response()->json($books);
    }

    public function popular(Request $request)
    {
        $limit = $request->input('limit');
        $books = DB::select("SELECT * FROM books ORDER BY swaps DESC LIMIT " . $limit);

        return response()->json($books);
    }
}
