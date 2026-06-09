<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'author',
        'description',
        'isbn',
        'publication_year',
        'cover_image',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'publication_year' => 'integer',
    ];

    // Relationships
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'book_genres')
            ->withTimestamps();
    }

    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists')
            ->withTimestamps();
    }

    /**
     * Get the average rating of the book.
     */
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Get the total number of reviews for the book.
     */
    public function getTotalReviewsAttribute()
    {
        return $this->reviews()->count();
    }

    public function updateAverageRating()
    {
        $this->average_rating = $this->reviews()
            ->where('status', 'approved')
            ->avg('rating') ?? 0;
        $this->total_reviews = $this->reviews()
            ->where('status', 'approved')
            ->count();
        $this->save();
    }
}
