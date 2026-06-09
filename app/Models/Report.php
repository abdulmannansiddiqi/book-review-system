<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'moderation_notes',
    ];

    /**
     * Get the user who submitted the report.
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the reported content (polymorphic).
     */
    public function reportable()
    {
        return $this->morphTo();
    }

    /**
     * Get the reported content details.
     */
    public function getContentAttribute()
    {
        $content = $this->reportable;

        switch ($this->reportable_type) {
            case Review::class:
                return [
                    'book_title' => $content->book->title,
                    'user_name' => $content->user->name,
                    'rating' => $content->rating,
                    'review_text' => $content->content,
                ];
            case User::class:
                return [
                    'name' => $content->name,
                    'email' => $content->email,
                    'profile_picture' => $content->profile_picture,
                    'joined_date' => $content->created_at,
                ];
            case Book::class:
                return [
                    'title' => $content->title,
                    'author' => $content->author,
                    'cover_image' => $content->cover_image,
                    'description' => $content->description,
                ];
            default:
                return null;
        }
    }

    /**
     * Get the type of the report.
     */
    public function getTypeAttribute()
    {
        return match ($this->reportable_type) {
            Review::class => 'review',
            User::class => 'user',
            Book::class => 'book',
            default => 'unknown',
        };
    }
}
