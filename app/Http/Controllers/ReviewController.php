<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class ReviewController extends Controller
{
    /**
     * Display latest approved reviews.
     */
    public function latest()
    {
        return Review::with(['user:id,name,profile_picture', 'book:id,title'])
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();
    }

    /**
     * Store a newly created review.
     */
    public function store(Request $request, Book $book)
    {
        DB::beginTransaction();

        try {
            Log::info('Attempting to store review', [
                'user_id' => Auth::id(),
                'book_id' => $book->id,
                'request_data' => $request->all()
            ]);

            // Check if user has already reviewed this book, including soft deleted reviews
            $existingReview = Review::withTrashed()
                ->where('user_id', Auth::id())
                ->where('book_id', $book->id)
                ->first();

            if ($existingReview) {
                DB::rollBack();
                Log::info('User has already reviewed this book', [
                    'user_id' => Auth::id(),
                    'book_id' => $book->id,
                    'existing_review_id' => $existingReview->id,
                    'is_deleted' => $existingReview->trashed()
                ]);

                if ($existingReview->trashed()) {
                    // If the review was soft deleted, restore it
                    $existingReview->restore();
                    return response()->json([
                        'message' => 'Your previous review has been restored. You can edit it instead.',
                        'review' => $existingReview
                    ], 422);
                }

                return response()->json([
                    'message' => 'You have already reviewed this book. You can edit your existing review instead.'
                ], 422);
            }

            $validated = $request->validate([
                'content' => 'required|string|min:10',
                'rating' => 'required|integer|between:1,5'
            ]);

            $review = new Review([
                'user_id' => Auth::id(),
                'book_id' => $book->id,
                'content' => $validated['content'],
                'rating' => $validated['rating'],
                'status' => 'pending',
                'helpful_votes' => 0,
                'is_highlighted' => false
            ]);

            Log::info('Creating new review', [
                'review_data' => $review->toArray()
            ]);

            if (!$review->save()) {
                throw new \Exception('Failed to save review');
            }

            // Update book's average rating
            $book->updateAverageRating();

            // Load relationships for the response
            $review->load(['user:id,name,profile_picture', 'responses']);

            Log::info('Review created successfully', [
                'review_id' => $review->id
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Review submitted successfully',
                'review' => $review
            ], 201);

        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Database error while saving review', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);
            return response()->json([
                'message' => 'Database error occurred while saving the review. Please try again.'
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error while saving review', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'An error occurred while saving the review. Please try again.'
            ], 500);
        }
    }

    /**
     * Update the specified review.
     */
    public function update(Request $request, Review $review)
    {
        // Check if the user owns this review
        if ($review->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|min:10|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Update the review
            $review->update([
                'rating' => $validated['rating'],
                'content' => $validated['content'],
                'status' => 'pending', // Reset to pending for moderation
            ]);

            // Update book's average rating
            $book = $review->book;
            $averageRating = $book->reviews()
                ->where('status', 'approved')
                ->avg('rating');

            $book->update([
                'average_rating' => $averageRating ?? 0
            ]);

            DB::commit();

            // Load relationships for response
            $review->load(['user:id,name,profile_picture', 'responses.user']);

            return response()->json([
                'message' => 'Review updated successfully',
                'review' => $review
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating review: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating review'], 500);
        }
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Review $review)
    {
        try {
            $this->authorize('delete', $review);

            DB::beginTransaction();

            $review->delete();

            // Update book's average rating
            $review->book->updateAverageRating();

            DB::commit();

            return response()->json(null, 204);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while deleting the review'
            ], 500);
        }
    }

    /**
     * Toggle helpful vote for a review.
     */
    public function toggleHelpfulVote(Review $review)
    {
        try {
            $user = Auth::user();
            $helpfulVotes = $review->toggleHelpfulVote($user);

            return response()->json([
                'helpful_votes' => $helpfulVotes,
                'is_helpful' => $review->isHelpfulFor($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your vote'
            ], 500);
        }
    }

    /**
     * Store a response to a review.
     */
    public function storeResponse(Request $request, Review $review)
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string|min:5'
            ]);

            $response = $review->responses()->create([
                'user_id' => Auth::id(),
                'content' => $validated['content']
            ]);

            $response->load('user');

            return response()->json($response, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while saving your response'
            ], 500);
        }
    }

    /**
     * Delete a response.
     */
    public function destroyResponse(ReviewResponse $response)
    {
        try {
            $this->authorize('delete', $response);
            $response->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the response'
            ], 500);
        }
    }

    /**
     * Toggle highlight status of a review.
     */
    public function toggleHighlight(Review $review)
    {
        try {
            $this->authorize('moderate', $review);

            $review->update([
                'is_highlighted' => !$review->is_highlighted
            ]);

            return response()->json([
                'is_highlighted' => $review->is_highlighted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the review status'
            ], 500);
        }
    }

    public function myReview(Book $book)
    {
        $review = $book->reviews()->where('user_id', auth()->id())->first();
        return response()->json(['review' => $review]);
    }
}
