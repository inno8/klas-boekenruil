<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_favorite_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->postJson('/favorites', [
            'book_id' => $book->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_cannot_unfavorite_others_favorite(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $book = Book::factory()->create();

        Favorite::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($intruder)->deleteJson('/favorites/' . $book->id);

        $response->assertStatus(404);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_store_validates_book_id_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/favorites', [
            'book_id' => 999999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('book_id');
    }
}
