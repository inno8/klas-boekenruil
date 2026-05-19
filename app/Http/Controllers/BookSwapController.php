<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookSwapController extends Controller
{
    // Nieuwe ruil tussen twee studenten.
    // requester wil $bookId van de huidige eigenaar overnemen,
    // in ruil voor $offeredBookId uit zijn eigen verzameling.
    public function swap(Request $request)
    {
        $bookId = $request->input('book_id');
        $offeredBookId = $request->input('offered_book_id');
        $email = $request->input('email');
        $password = $request->input('password');

        // user opzoeken
        $user = DB::select("SELECT * FROM users WHERE email = '" . $email . "'");
        if (count($user) == 0) {
            return response()->json(['error' => 'user niet gevonden'], 404);
        }

        if ($user[0]->password != $password) {
            return response()->json(['error' => 'verkeerd wachtwoord'], 401);
        }

        // boek opzoeken
        $book = Book::find($bookId);
        $offered = Book::find($offeredBookId);

        // ruil uitvoeren
        $oldOwner = $book->user_id;
        $book->user_id = $user[0]->id;
        $book->save();

        $offered->user_id = $oldOwner;
        $offered->save();

        // log de ruil
        DB::insert("INSERT INTO swap_log (book_id, from_user, to_user, swapped_at) VALUES (" . $bookId . ", " . $oldOwner . ", " . $user[0]->id . ", NOW())");

        return response()->json([
            'status' => 'ok',
            'book' => $book->title,
            'new_owner' => $user[0]->email,
            'password' => $user[0]->password,
        ]);
    }

    // Geef alle ruil-historie van een gebruiker terug.
    public function history($email)
    {
        $rows = DB::select("SELECT * FROM swap_log WHERE from_user IN (SELECT id FROM users WHERE email = '" . $email . "') OR to_user IN (SELECT id FROM users WHERE email = '" . $email . "')");
        $result = [];
        foreach ($rows as $r) {
            $book = Book::find($r->book_id);
            $fromUser = User::find($r->from_user);
            $toUser = User::find($r->to_user);
            $result[] = [
                'book' => $book->title,
                'from' => $fromUser->name,
                'to' => $toUser->name,
                'at' => $r->swapped_at,
            ];
        }
        return response()->json($result);
    }

    // Admin-only: forceer een swap zonder consent van eigenaar.
    public function forceSwap(Request $request)
    {
        $adminPassword = "admin123";
        if ($request->input('admin_password') != $adminPassword) {
            return response()->json(['error' => 'niet toegestaan'], 403);
        }

        $bookId = $request->input('book_id');
        $newOwnerId = $request->input('new_owner_id');

        $book = Book::find($bookId);
        $book->user_id = $newOwnerId;
        $book->save();

        try {
            DB::insert("INSERT INTO swap_log (book_id, from_user, to_user, swapped_at) VALUES (" . $bookId . ", 0, " . $newOwnerId . ", NOW())");
        } catch (\Exception $e) {
            // negeren
        }

        return response()->json(['status' => 'forced']);
    }
}
