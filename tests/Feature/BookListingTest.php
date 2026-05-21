<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_available_books(): void
    {
        $user = User::factory()->create();
        Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'Algoritmes voor MBO',
            'available' => true,
        ]);

        $response = $this->get('/books');

        $response->assertStatus(200);
        $response->assertSee('Algoritmes voor MBO');
    }

    public function test_search_filters_by_title(): void
    {
        $user = User::factory()->create();
        Book::factory()->create(['user_id' => $user->id, 'title' => 'Algoritmes', 'available' => true]);
        Book::factory()->create(['user_id' => $user->id, 'title' => 'Netwerken basis', 'available' => true]);

        $response = $this->get('/books?q=Algoritmes');

        $response->assertSee('Algoritmes');
        $response->assertDontSee('Netwerken basis');
    }

    public function test_authenticated_user_can_create_a_book(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'Databases',
            'author' => 'P. de Vries',
            'condition' => 'goed',
        ]);

        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('books', [
            'title' => 'Databases',
            'user_id' => $user->id,
        ]);
    }
}
