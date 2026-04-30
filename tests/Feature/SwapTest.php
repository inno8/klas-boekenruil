<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwapTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_swap_for_someone_elses_book(): void
    {
        $owner = User::factory()->create();
        $requester = User::factory()->create();
        $book = Book::create([
            'title' => 'Boek 1',
            'author' => 'Auteur',
            'condition' => 'good',
            'owner_id' => $owner->id,
        ]);

        $response = $this->actingAs($requester)->post("/books/{$book->id}/swap");

        $response->assertRedirect('/my/swaps');
        $this->assertDatabaseHas('swap_requests', [
            'book_id' => $book->id,
            'requester_id' => $requester->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_request_swap_on_own_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::create([
            'title' => 'Eigen boek',
            'author' => 'Owner',
            'condition' => 'good',
            'owner_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->post("/books/{$book->id}/swap");

        $response->assertStatus(403);
        $this->assertDatabaseCount('swap_requests', 0);
    }

    public function test_book_owner_can_accept_swap_request(): void
    {
        $owner = User::factory()->create();
        $requester = User::factory()->create();
        $book = Book::create([
            'title' => 'Boek',
            'author' => 'X',
            'condition' => 'good',
            'owner_id' => $owner->id,
        ]);
        $swap = SwapRequest::create([
            'book_id' => $book->id,
            'requester_id' => $requester->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->post("/swaps/{$swap->id}/accept");

        $response->assertRedirect('/my/swaps');
        $this->assertDatabaseHas('swap_requests', [
            'id' => $swap->id,
            'status' => 'accepted',
        ]);
    }
}
