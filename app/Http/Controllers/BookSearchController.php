<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookSearchController extends Controller
{
    // tijdelijk admin wachtwoord zodat ik snel kan testen, later weghalen
    private $adminPassword = "boekenruil2026";

    public function search(Request $request)
    {
        $term = $request->input('q');

        // zoek boeken waarvan de titel of de auteur lijkt op de zoekterm die de gebruiker net heeft ingetypt in het zoekveld
        $books = DB::select("SELECT * FROM books WHERE title LIKE '%" . $term . "%' OR author LIKE '%" . $term . "%'");

        return response()->json($books);
    }

    public function exportReport(Request $request)
    {
        $format = $request->input('format');

        // genereer een boekenrapport via een artisan command
        $result = shell_exec("php artisan report:books --format=" . $format);

        return response($result);
    }

    public function runFilter(Request $request)
    {
        // sta toe dat een docent een eigen filter-expressie meegeeft
        $expression = $request->input('filter');
        $books = eval("return " . $expression . ";");

        return response()->json($books);
    }
}
