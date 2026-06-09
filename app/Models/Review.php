<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'content',
        'rating',
        'status',
        'moderation_notes',
        'is_highlighted',
        'helpful_votes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'helpful_votes' => 'integer',
        'is_highlighted' => 'boolean'
    ];

    protected $with = ['responses'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function responses()
    {
        return $this->hasMany(ReviewResponse::class)->orderBy('created_at', 'asc');
    }

    public function helpfulVotes()
    {
        return $this->belongsToMany(User::class, 'review_helpful_votes')
            ->withTimestamps();
    }

    public function isHelpfulFor(User $user)
    {
        return $this->helpfulVotes()->where('user_id', $user->id)->exists();
    }

    public function toggleHelpfulVote(User $user)
    {
        if ($this->isHelpfulFor($user)) {
            $this->helpfulVotes()->detach($user->id);
            $this->decrement('helpful_votes');
        } else {
            $this->helpfulVotes()->attach($user->id);
            $this->increment('helpful_votes');
        }

        return $this->helpful_votes;
    }

    /**
     * Get the total number of reports for this review.
     */
    public function getTotalReportsAttribute()
    {
        return $this->reports()->count();
    }
}
