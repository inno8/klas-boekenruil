<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bouwt de read-only "wat staat er op mijn wishlist + hoeveel zijn er
 * beschikbaar?"-projectie. Apart van de controller zodat de controller
 * alleen HTTP-concerns afhandelt (validation, response shape) en deze
 * service alleen domein-logica + data access doet.
 *
 * Vorige docent-feedback: available_count + N+1 boek-lookups stonden
 * in WishlistController::show(). Dat mengt presentation (controller),
 * business logic ("wat is available_count?") en data access (de
 * loops). Door die drie te scheiden:
 *
 *   - kunnen we de logica los unit-testen zonder een HTTP-request te
 *     stubben
 *   - kunnen we het N+1 patroon vervangen door één gegroepeerde query
 *     (groupBy + count) ipv per-item een SELECT
 *   - blijft de controller leesbaar als "wat is de input, wat is de
 *     output" — geen business-rules om doorheen te scrollen
 */
class WishlistViewService
{
    /**
     * @return array<int, array{title:string, author:string, added_at:string, available_count:int}>
     */
    public function buildFor(int $userId): array
    {
        // 1. Pak alle wishlist-items van deze user via de query
        //    builder (geen raw SQL meer). Parameters worden
        //    automatisch gebonden, dus geen SQL-injectie via $userId.
        $items = DB::table('wishlist')
            ->where('user_id', $userId)
            ->orderByDesc('added_at')
            ->get(['title', 'author', 'added_at']);

        if ($items->isEmpty()) {
            return [];
        }

        // 2. Tel beschikbare boeken voor ELKE wishlist-titel in ÉÉN
        //    query (was: N afzonderlijke SELECTs in een loop). Resultaat
        //    is een lookup-map title → count, die we op stap 3 joinen
        //    met de wishlist-items zonder verder DB-werk te doen.
        $titles = $items->pluck('title')->unique()->all();
        $availableCounts = $this->availableCountsByTitle($titles);

        // 3. Bouw de uitgaande projectie. Eén pass over $items, geen
        //    extra queries. Defensieve cast naar int voor de count
        //    zodat de JSON-response een number is, geen string.
        return $items
            ->map(fn ($item) => [
                'title' => $item->title,
                'author' => $item->author,
                'added_at' => $item->added_at,
                'available_count' => (int) ($availableCounts[$item->title] ?? 0),
            ])
            ->all();
    }

    /**
     * Tel per titel hoeveel Book-rijen er met available=true zijn.
     *
     * @param array<int, string> $titles
     * @return array<string, int>
     */
    private function availableCountsByTitle(array $titles): array
    {
        if (empty($titles)) {
            return [];
        }

        // Eloquent op het Book-model omdat we de "available" semantiek
        // op het model willen houden (mocht dat ooit een scope worden).
        return Book::whereIn('title', $titles)
            ->where('available', true)
            ->select('title', DB::raw('COUNT(*) as cnt'))
            ->groupBy('title')
            ->pluck('cnt', 'title')
            ->all();
    }
}
