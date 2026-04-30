<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    // owner_id is gewoon een fk naar users, geen extra cast nodig denk ik
    protected $fillable = ['title', 'author', 'isbn', 'condition', 'owner_id'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // formatted title voor de bookcards op de zoekpagina
    public function getDisplayTitleAttribute()
    {
        return strtoupper($this->title) . ' - ' . $this->author;
    }

    // even snel een filter op staat (used/new/good)
    public function scopeByCondition($query, $condition)
    {
        return $query->where('condition', '=', $condition);
    }
}
