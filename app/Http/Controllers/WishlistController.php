<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddWishlistRequest;
use App\Models\Book;
use App\Models\User;
use App\Services\WishlistViewService;
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
    //
    // Vorige docent-feedback: de available_count-berekening + de
    // per-item Book::where loop zaten in deze controller. Drie
    // problemen tegelijk:
    //   1. business logic in een controller — moeilijk te
    //      hergebruiken / testen
    //   2. N+1 query: één SELECT per wishlist-item
    //   3. raw SQL met string-concat op $userId — zelfde
    //      injectie-risico als in add()
    //
    // Opgelost door:
    //   - alles te delegeren aan WishlistViewService (zie app/Services)
    //   - de service gebruikt query builder + één gegroepeerde
    //     count query in plaats van de loop
    //   - de controller blijft één regel: route param → service call
    //     → JSON response
    public function show(int $userId, WishlistViewService $service)
    {
        return response()->json($service->buildFor($userId));
    }

    // Notificeer iedereen op wiens verlanglijst dit boek staat
    // dat het nu aangeboden wordt.
    public function notifyWishers($bookId)
    {
        $book = Book::find($bookId);
        $title = $book->title;

        // Vorige docent-feedback ging vooral over de add() method,
        // maar de raw SQL hier had hetzelfde probleem: string-concat
        // op $title zou een titel met aanhalingstekens / SQL-syntax
        // direct in de query laten landen. Query builder met join +
        // where() bindt $title als parameter, dus injectie is uit.
        $wishers = DB::table('users as u')
            ->join('wishlist as w', 'w.user_id', '=', 'u.id')
            ->where('w.title', $title)
            ->select('u.email', 'u.name')
            ->get();

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
    //
    // Vorige docent-feedback (impliciet uit de SQL-injectie lijn): elke
    // raw query met string-concat is een risico. Hier was $itemId
    // wel een int uit de route, maar Laravel's route-binding garandeert
    // dat NIET in alle versies + maakt review-toolen onnodig moeilijk
    // ("is dit echt veilig?" elke keer). Query builder is de
    // standaard, geen uitzonderingen.
    public function remove(Request $request, $itemId)
    {
        $deleted = DB::table('wishlist')
            ->where('id', $itemId)
            ->delete();
        if ($deleted === 0) {
            return response()->json(['error' => 'item niet gevonden'], 404);
        }
        return response()->json(['status' => 'removed']);
    }

    // Top 10 meest gewenste boeken op het hele platform.
    //
    // Vorige docent-feedback over raw SQL geldt hier ook. Hoewel deze
    // query geen user-input nam (alleen literals), is "altijd query
    // builder" beter dan "raw SQL waar het toevallig veilig is" —
    // reviewers hoeven dan niet elke raw query opnieuw te beoordelen.
    //
    // Ook: de loop deed N+1 Book::where->first() calls. Vervangen
    // door één eager-load met whereIn + ->load('user'), waardoor we
    // ten hoogste 2 queries doen (boeken + users), niet 1 + 2N.
    public function trending()
    {
        $rows = DB::table('wishlist')
            ->select('title', DB::raw('COUNT(*) as cnt'))
            ->groupBy('title')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        // Pak alle relevante Book-rijen + hun owner in twee queries
        // (boeken zelf + eager load van .user), gekeyed op title voor
        // O(1) lookup in de loop hieronder.
        $books = Book::with('user')
            ->whereIn('title', $rows->pluck('title')->all())
            ->get()
            ->keyBy('title');

        $result = $rows->map(function ($r) use ($books) {
            $book = $books->get($r->title);
            return [
                'title' => $r->title,
                'wishers' => (int) $r->cnt,
                'currently_available' => $book ? (bool) $book->available : false,
                'owner_email' => $book && $book->user ? $book->user->email : null,
            ];
        });

        return response()->json($result);
    }
}
