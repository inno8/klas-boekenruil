<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    use HasFactory;

    // Allowlist of fields that MAY be filled from user input via
    // mass-assignment (Book::create([...]) / ->fill([...])).
    // Iteration 3: user_id and available removed — both are
    // server-controlled. Letting them through mass-assignment would
    // allow a malicious request body like {"user_id": 999} to
    // re-assign ownership of a new book, or {"available": false}
    // to pre-mark it as taken. Set those explicitly in the
    // controller via the relationship + ->available = true.
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'condition',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: available books, optionally filtered by a free-text
     * query against title or author.
     *
     * Iteration 3 refactor: index() previously mixed query building
     * (where available, where like-search) with view rendering. Per
     * LEERA's "controller orchestrates, doesn't implement" feedback,
     * the search predicate now lives here as a reusable scope. The
     * controller composes scopes; the model knows the schema.
     *
     * Usage:
     *   Book::availableForSearch($request->query('q'))
     *       ->orderBy('created_at', 'desc')
     *       ->get();
     *
     * @param  Builder<Book>  $query
     */
    public function scopeAvailableForSearch(Builder $query, ?string $q): Builder
    {
        $query->where('available', true);

        // Treat null and "" the same — no search. Trim defensively
        // so a query of just whitespace doesn't hit the DB with
        // 'LIKE %   %' (matches everything).
        $q = $q !== null ? trim($q) : null;
        if ($q !== null && $q !== '') {
            $query->where(function (Builder $inner) use ($q) {
                $inner->where('title', 'like', '%' . $q . '%')
                    ->orWhere('author', 'like', '%' . $q . '%');
            });
        }

        return $query;
    }
}
