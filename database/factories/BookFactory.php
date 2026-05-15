<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        $conditions = ['nieuw', 'goed', 'gebruikt', 'beschadigd'];

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->numerify('978#-#-####-####-#'),
            'condition' => fake()->randomElement($conditions),
            'description' => fake()->paragraph(),
            'available' => true,
        ];
    }
}
