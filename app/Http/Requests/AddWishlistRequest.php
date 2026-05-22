<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validatie voor POST /wishlist/add.
 *
 * Verplaatst de validatielogica uit de controller (vorige iter feedback
 * van de docent) zodat:
 *   - de controller dun blijft en alleen HTTP-concerns afhandelt
 *   - alleen geldige data ooit de business logic bereikt
 *   - de regels herbruikbaar zijn (bv. via een toekomstige API-route)
 *   - we ze los kunnen testen
 */
class AddWishlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Iedereen die ingelogd is mag aan zijn eigen wishlist toevoegen.
        // Verfijning (alleen voor de eigen user_id) volgt in een policy
        // als de auth-flow er staat — buiten scope van deze iter.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'author' => ['required', 'string', 'min:1', 'max:255'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Vriendelijke Nederlandse foutmeldingen (de student is de eindgebruiker).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Vul een titel in voor het boek.',
            'title.max' => 'De titel mag maximaal 255 tekens zijn.',
            'author.required' => 'Vul de auteur in.',
            'author.max' => 'De auteursnaam mag maximaal 255 tekens zijn.',
            'user_id.required' => 'user_id ontbreekt.',
            'user_id.integer' => 'user_id moet een geheel getal zijn.',
            'user_id.exists' => 'Deze gebruiker bestaat niet.',
        ];
    }
}
