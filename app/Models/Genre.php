<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    /**
     * Get the books in this genre.
     */
    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_genres')
            ->withTimestamps();
    }

    /**
     * Get the users who prefer this genre.
     */
    public function preferredByUsers()
    {
        return $this->belongsToMany(User::class, 'user_preferred_genres')
            ->withTimestamps();
    }

    /**
     * Get the total number of books in this genre.
     */
    public function getTotalBooksAttribute()
    {
        return $this->books()->count();
    }
}
