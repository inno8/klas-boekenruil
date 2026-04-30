<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function exportGrades(Request $request)
    {
        $minRating = $request->query('min_rating', 0);

        // even snel een query, de docent wil alleen boeken met hoge rating
        $books = DB::select("select * from books where rating > $minRating");

        $path = '/tmp/leera-export.csv';

        $csv = "id,titel,auteur,rating\n";
        foreach ($books as $book) {
            $csv .= $book->id . ',' . $book->title . ',' . $book->author . ',' . $book->rating . "\n";
        }

        file_put_contents($path, $csv);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cijferlijst.csv"');
        readfile($path);
        exit;
    }
}
