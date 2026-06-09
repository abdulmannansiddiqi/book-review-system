<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;

class AdminReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = \App\Models\Review::with(['book', 'user', 'reports', 'book'])->get();
        $reviews = $reviews->map(function ($review) {
            $user = $review->user;
            $book = $review->book;
            return [
                'id' => $review->id,
                'book' => [
                    'cover_image' => $book ? $book->cover_image : null,
                    'title' => $book ? $book->title : '',
                    'author' => $book ? $book->author : '',
                ],
                'user' => [
                    'profile_picture' => $user ? $user->profile_picture : null,
                    'name' => $user ? $user->name : '',
                ],
                'rating' => $review->rating,
                'content' => $review->content,
                'created_at' => $review->created_at,
                'status' => $review->status ?? 'active',
                'total_reports' => $review->reports ? $review->reports->count() : 0,
            ];
        });
        return response()->json(['reviews' => $reviews]);
    }

    public function show($id)
    {
        $review = Review::with(['book', 'user'])->findOrFail($id);
        $data = [
            'id' => $review->id,
            'book' => $review->book,
            'user' => $review->user,
            'rating' => $review->rating,
            'content' => $review->content,
            'status' => $review->status ?? 'active',
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);
        $review = Review::create($validated);
        $review->refresh();
        return response()->json(['message' => 'Review created', 'review' => $review], 201);
    }

    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $validated = $request->validate([
            'book_id' => 'sometimes|required|exists:books,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'review' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
        $review->fill($validated);
        $review->save();
        $review->refresh();
        return response()->json(['message' => 'Review updated', 'review' => $review]);
    }

    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();
        return response()->json(['message' => 'Review deleted']);
    }

    public function moderate(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string',
        ]);
        if ($validated['action'] === 'approve') {
            $review->status = 'approved';
        } elseif ($validated['action'] === 'reject') {
            $review->status = 'rejected';
        }
        $review->moderation_notes = $validated['notes'] ?? null;
        $review->save();
        return response()->json(['message' => 'Review moderated', 'review' => $review]);
    }
}
