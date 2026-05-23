<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Swap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookSwapTest extends TestCase
{
    use RefreshDatabase;

    public function test_swap_transfers_ownership_and_logs_history(): void
    {
        [$alice, $bob] = User::factory()->count(2)->create();
        $bobsBook = Book::factory()->create(['user_id' => $bob->id]);
        $alicesBook = Book::factory()->create(['user_id' => $alice->id]);

        Sanctum::actingAs($alice);
        $response = $this->postJson('/api/swap', [
            'book_id' => $bobsBook->id,
            'offered_book_id' => $alicesBook->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($alice->id, $bobsBook->fresh()->user_id);
        $this->assertEquals($bob->id, $alicesBook->fresh()->user_id);
        $this->assertDatabaseHas('swaps', [
            'book_id' => $bobsBook->id,
            'from_user_id' => $bob->id,
            'to_user_id' => $alice->id,
        ]);
    }

    public function test_cannot_swap_your_own_book(): void
    {
        $alice = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $alice->id]);
        $other = Book::factory()->create(['user_id' => $alice->id]);

        Sanctum::actingAs($alice);
        $response = $this->postJson('/api/swap', [
            'book_id' => $book->id,
            'offered_book_id' => $other->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_offer_book_you_dont_own(): void
    {
        [$alice, $bob] = User::factory()->count(2)->create();
        $bobsBook = Book::factory()->create(['user_id' => $bob->id]);
        $alsoBobs = Book::factory()->create(['user_id' => $bob->id]);

        Sanctum::actingAs($alice);
        $response = $this->postJson('/api/swap', [
            'book_id' => $bobsBook->id,
            'offered_book_id' => $alsoBobs->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_swap_is_rejected(): void
    {
        [$alice, $bob] = User::factory()->count(2)->create();
        $b1 = Book::factory()->create(['user_id' => $bob->id]);
        $b2 = Book::factory()->create(['user_id' => $alice->id]);

        $response = $this->postJson('/api/swap', [
            'book_id' => $b1->id,
            'offered_book_id' => $b2->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_response_does_not_leak_password(): void
    {
        [$alice, $bob] = User::factory()->count(2)->create();
        $bobsBook = Book::factory()->create(['user_id' => $bob->id]);
        $alicesBook = Book::factory()->create(['user_id' => $alice->id]);

        Sanctum::actingAs($alice);
        $response = $this->postJson('/api/swap', [
            'book_id' => $bobsBook->id,
            'offered_book_id' => $alicesBook->id,
        ]);

        $response->assertJsonMissing(['password']);
        $response->assertJsonMissingPath('book.user.password');
    }
}
