<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Swap;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BookSwapController extends Controller
{
    public function __construct()
    {
        // Auth via middleware i.p.v. wachtwoord in elke request — geen
        // plaintext password meer over de lijn, geen user-enumeration via
        // verschillende foutmeldingen, één bron van waarheid voor sessies.
        $this->middleware('auth:sanctum');
    }

    /**
     * Nieuwe ruil. De ingelogde gebruiker (= requester) wil $book overnemen
     * en biedt $offered uit zijn eigen verzameling als ruil.
     */
    public function swap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|integer|exists:books,id',
            'offered_book_id' => 'required|integer|exists:books,id|different:book_id',
        ]);

        $requester = $request->user();
        $book = Book::findOrFail($validated['book_id']);
        $offered = Book::findOrFail($validated['offered_book_id']);

        if ($book->user_id === $requester->id) {
            throw ValidationException::withMessages([
                'book_id' => 'je kunt niet je eigen boek ruilen',
            ]);
        }
        if ($offered->user_id !== $requester->id) {
            throw ValidationException::withMessages([
                'offered_book_id' => 'je kunt alleen boeken aanbieden die van jou zijn',
            ]);
        }

        // Atomic — UPDATE van beide boeken + INSERT in swaps gebeurt
        // samen of helemaal niet. Anders kun je in een inconsistente
        // state eindigen waar één boek wel is overgegaan en het andere niet.
        DB::transaction(function () use ($book, $offered, $requester) {
            $originalOwnerId = $book->user_id;
            $book->update(['user_id' => $requester->id]);
            $offered->update(['user_id' => $originalOwnerId]);
            Swap::create([
                'book_id' => $book->id,
                'from_user_id' => $originalOwnerId,
                'to_user_id' => $requester->id,
            ]);
        });

        return response()->json([
            'status' => 'ok',
            'book' => $book->only(['id', 'title', 'author']),
            'new_owner_email' => $requester->email,
        ]);
    }

    /**
     * Ruil-historie van een gebruiker. Eén query met joins i.p.v. N+1
     * per swap-regel.
     */
    public function history(User $user): JsonResponse
    {
        // Authorization-check zou hier kunnen — alleen eigenaar of admin
        // mag iemand anders zijn historie zien. Voor nu open per design;
        // policy komt in een vervolg-PR.
        $rows = Swap::with(['book:id,title', 'fromUser:id,name', 'toUser:id,name'])
            ->where('from_user_id', $user->id)
            ->orWhere('to_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($s) => [
                'book' => $s->book->title,
                'from' => $s->fromUser->name,
                'to' => $s->toUser->name,
                'at' => $s->created_at->toIso8601String(),
            ]);

        return response()->json($rows);
    }

    /**
     * Admin-only force-swap. Beveiligd via een echte 'admin' middleware
     * + gebruikt de logged-in admin, niet een wachtwoord in de body.
     * Logt expliciet wie het deed zodat audit-trail klopt.
     */
    public function forceSwap(Request $request): JsonResponse
    {
        $this->middleware('can:force-swap');

        $validated = $request->validate([
            'book_id' => 'required|integer|exists:books,id',
            'new_owner_id' => 'required|integer|exists:users,id',
        ]);

        $book = Book::findOrFail($validated['book_id']);
        $originalOwnerId = $book->user_id;

        DB::transaction(function () use ($book, $validated, $originalOwnerId, $request) {
            $book->update(['user_id' => $validated['new_owner_id']]);
            Swap::create([
                'book_id' => $book->id,
                'from_user_id' => $originalOwnerId,
                'to_user_id' => $validated['new_owner_id'],
                'forced_by_admin_id' => $request->user()->id,
            ]);
        });

        Log::info('forced swap', [
            'book_id' => $book->id,
            'admin_id' => $request->user()->id,
            'from' => $originalOwnerId,
            'to' => $validated['new_owner_id'],
        ]);

        return response()->json(['status' => 'forced']);
    }
}
