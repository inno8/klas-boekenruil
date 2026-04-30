<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_books(): void
    {
        $user = User::factory()->create();
        Book::create([
            'title' => 'Laravel voor beginners',
            'author' => 'Iemand',
            'condition' => 'good',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/books');

        $response->assertStatus(200);
        $response->assertSee('Laravel voor beginners');
    }

    public function test_user_can_create_book(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'Nieuw boek',
            'author' => 'Auteur X',
            'isbn' => '978-1234567890',
            'condition' => 'new',
        ]);

        $response->assertRedirect('/books');
        $this->assertDatabaseHas('books', [
            'title' => 'Nieuw boek',
            'owner_id' => $user->id,
        ]);
    }

    public function test_user_cannot_edit_others_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::create([
            'title' => 'Privé boek',
            'author' => 'Owner',
            'condition' => 'good',
            'owner_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->put("/books/{$book->id}", [
            'title' => 'Gehackt',
            'author' => 'Aanvaller',
            'condition' => 'used',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Privé boek']);
    }

    public function test_search_does_not_break_on_quote(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get("/books?search=O'Brien");

        $response->assertStatus(200);
    }
}
