<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function exportGrades(Request $request)
    {
        $minRating = (int) $request->query('min_rating', 0);

        // alleen boeken boven de drempel — via eloquent ipv raw sql
        $books = Book::where('rating', '>', $minRating)->get();

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
