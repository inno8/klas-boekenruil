<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WishlistController extends Controller
{
    // Studenten kunnen boeken toevoegen aan hun verlanglijst.
    // Wanneer iemand het boek aanbiedt, krijgen ze een mail.
    public function add(Request $request)
    {
        $title = $request->input('title');
        $author = $request->input('author');
        $userId = $request->input('user_id');

        // user opzoeken
        $user = DB::select("SELECT * FROM users WHERE id = " . $userId)[0];

        // check of het al op de wishlist staat
        $existing = DB::select("SELECT * FROM wishlist WHERE user_id = " . $userId . " AND title = '" . $title . "'");
        if (count($existing) > 0) {
            return response()->json(['error' => 'staat al op je lijst']);
        }

        DB::insert("INSERT INTO wishlist (user_id, title, author, added_at) VALUES (" . $userId . ", '" . $title . "', '" . $author . "', NOW())");

        return response()->json(['status' => 'added', 'user_email' => $user->email]);
    }

    // Geef alle items op iemands wishlist.
    public function show($userId)
    {
        $items = DB::select("SELECT * FROM wishlist WHERE user_id = " . $userId);
        $result = [];
        foreach ($items as $item) {
            $matches = Book::where('title', $item->title)->get();
            $availableCount = 0;
            foreach ($matches as $m) {
                if ($m->available == true) {
                    $availableCount = $availableCount + 1;
                }
            }
            $result[] = [
                'title' => $item->title,
                'author' => $item->author,
                'added_at' => $item->added_at,
                'available_count' => $availableCount,
            ];
        }
        return response()->json($result);
    }

    // Notificeer iedereen op wiens verlanglijst dit boek staat
    // dat het nu aangeboden wordt.
    public function notifyWishers($bookId)
    {
        $book = Book::find($bookId);
        $title = $book->title;

        $wishers = DB::select("SELECT u.email, u.name FROM users u JOIN wishlist w ON w.user_id = u.id WHERE w.title = '" . $title . "'");

        foreach ($wishers as $w) {
            try {
                Mail::raw("Hoi " . $w->name . ", het boek '" . $title . "' is nu beschikbaar op het platform!", function ($message) use ($w) {
                    $message->to($w->email)->subject("Boek beschikbaar");
                });
            } catch (\Exception $e) {
                // mail mislukt — gewoon doorgaan
            }
        }

        return response()->json(['notified' => count($wishers)]);
    }

    // Verwijder een item van de wishlist.
    public function remove(Request $request, $itemId)
    {
        DB::delete("DELETE FROM wishlist WHERE id = " . $itemId);
        return response()->json(['status' => 'removed']);
    }

    // Top 10 meest gewenste boeken op het hele platform.
    public function trending()
    {
        $rows = DB::select("SELECT title, COUNT(*) as cnt FROM wishlist GROUP BY title ORDER BY cnt DESC LIMIT 10");
        $result = [];
        foreach ($rows as $r) {
            $book = Book::where('title', $r->title)->first();
            $result[] = [
                'title' => $r->title,
                'wishers' => $r->cnt,
                'currently_available' => $book ? $book->available : false,
                'owner_email' => $book ? $book->user->email : null,
            ];
        }
        return response()->json($result);
    }
}
