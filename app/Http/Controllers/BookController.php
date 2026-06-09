<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Review;

class BookController extends Controller
{
    /**
     * Display a listing of the books.
     */
    public function index(Request $request)
    {
        $query = Book::query()->with(['genres', 'reviews']);

        // Apply search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        // Apply genre filter
        if ($request->has('genre') && $request->input('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->input('genre'));
            });
        }

        // Apply sorting
        $sortBy = $request->input('sort', 'title');
        switch ($sortBy) {
            case 'rating':
                $query->withAvg('reviews', 'rating')
                    ->orderByDesc('reviews_avg_rating');
                break;
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            default:
                $query->orderBy('title');
                break;
        }

        // Get paginated results
        $perPage = $request->input('per_page', 12);
        $books = $query->paginate($perPage);

        return response()->json([
            'books' => $books->items(),
            'total' => $books->total(),
            'current_page' => $books->currentPage(),
            'per_page' => $books->perPage(),
            'last_page' => $books->lastPage(),
        ]);
    }

    /**
     * Display a listing of featured books.
     */
    public function featured(Request $request)
    {
        $user = auth()->user();
        $limit = 6;

        // Logic for users with preferred genres
        if ($user && $user->preferredGenres()->exists()) {
            $preferredGenreIds = $user->preferredGenres->pluck('id');

            $featuredBooks = Book::with(['genres', 'reviews'])
                ->whereHas('reviews')
                ->whereHas('genres', function ($query) use ($preferredGenreIds) {
                    $query->whereIn('genres.id', $preferredGenreIds);
                })
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->take($limit)
                ->get();

            // If not enough personalized books, fill with general top-rated books
            $bookCount = $featuredBooks->count();
            if ($bookCount < $limit) {
                $generalTopBooks = Book::with(['genres', 'reviews'])
                    ->whereHas('reviews')
                    ->whereNotIn('id', $featuredBooks->pluck('id')) // Exclude already selected books
                    ->withAvg('reviews', 'rating')
                    ->orderByDesc('reviews_avg_rating')
                    ->take($limit - $bookCount)
                    ->get();

                $featuredBooks = $featuredBooks->concat($generalTopBooks);
            }
        } else {
            // Fallback for guests or users without preferred genres
            $featuredBooks = Book::with(['genres', 'reviews'])
                ->whereHas('reviews')
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->take($limit)
                ->get();
        }

        return response()->json($featuredBooks);
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book)
    {
        $book->load(['genres', 'reviews.user']);
        return response()->json($book);
    }

    /**
     * Get reviews for a specific book.
     */
    public function reviews(Request $request, Book $book)
{
    $userReview = null;
    $userId = $request->input('user_id');

    // Always fetch all reviews with user, ordered by creation date
    $allReviews = $book->reviews()
        ->with('user')
        ->orderByDesc('created_at');

    // If user ID provided, exclude user's own review from the list
    if ($userId) {
        $userReview = \App\Models\Review::where('book_id', $book->id)
            ->where('user_id', $userId)
            ->first();

        $allReviews->where('user_id', '!=', $userId);
    }

    return response()->json([
        'reviews' => $allReviews->get(),
        'user_review' => $userReview,
    ]);
}
}
