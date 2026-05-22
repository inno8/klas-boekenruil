<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddWishlistRequest;
use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WishlistController extends Controller
{
    // Studenten kunnen boeken toevoegen aan hun verlanglijst.
    // Wanneer iemand het boek aanbiedt, krijgen ze een mail.
    //
    // Vorige docent-feedback: ruwe request input zonder validatie =
    // veiligheidsrisico. Opgelost door AddWishlistRequest te
    // type-hinten: Laravel valideert automatisch voordat deze method
    // draait, en geeft de student een 422 met duidelijke foutmeldingen
    // als er iets mist of fout is. De controller blijft dun.
    public function add(AddWishlistRequest $request)
    {
        // Na validatie: validated() geeft ALLEEN de gevalideerde
        // velden terug. Geen sluipverkeer van willekeurige request
        // input naar de business logic.
        $validated = $request->validated();
        $title = $validated['title'];
        $author = $validated['author'];
        $userId = $validated['user_id'];

        // user opzoeken — findOrFail i.p.v. raw SQL + [0]
        // → query builder gebruikt prepared statements, dus geen SQL-injectie meer
        // → findOrFail() geeft een 404 als de user niet bestaat (geen fatal error)
        $user = User::findOrFail($userId);

        // check of het al op de wishlist staat — query builder met where()
        // bindt de parameters automatisch, dus title kan geen SQL meer bevatten
        $existing = DB::table('wishlist')
            ->where('user_id', $userId)
            ->where('title', $title)
            ->exists();
        if ($existing) {
            return response()->json(['error' => 'staat al op je lijst']);
        }

        // INSERT via de query builder — bindt de waardes automatisch en
        // accepteert een DB::raw('NOW()') voor de timestamp zodat de
        // database de tijd zet en we niet meer met PHP-strings stoeien.
        DB::table('wishlist')->insert([
            'user_id' => $userId,
            'title' => $title,
            'author' => $author,
            'added_at' => DB::raw('NOW()'),
        ]);

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

        // Vorige docent-feedback: de try/catch slikt alle mail-fouten
        // zonder logging — debugging onmogelijk. Oplossing: log de
        // uitzondering met genoeg context (boek + ontvanger) zodat we
        // bij een mailprobleem precies kunnen zien wat er misging.
        // We blijven wel doorgaan met de andere wishers — één bounce
        // mag niet de hele notify-flow stilleggen — maar de fout
        // verdwijnt niet meer in het niets.
        $sent = 0;
        $failed = 0;
        foreach ($wishers as $w) {
            try {
                Mail::raw(
                    "Hoi " . $w->name . ", het boek '" . $title
                        . "' is nu beschikbaar op het platform!",
                    function ($message) use ($w) {
                        $message->to($w->email)->subject("Boek beschikbaar");
                    }
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('wishlist.notifyWishers: mail mislukt', [
                    'book_id' => $bookId,
                    'book_title' => $title,
                    'recipient_email' => $w->email,
                    'exception_class' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'notified' => $sent,
            'failed' => $failed,
            'total' => count($wishers),
        ]);
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
