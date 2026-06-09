<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = \App\Models\User::with(['wishlists', 'reviews'])->get();
        $users = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
                'total_reviews' => $user->reviews ? $user->reviews->count() : 0,
                'is_banned' => $user->is_banned,
                'ban_reason' => $user->ban_reason,
                'ban_expires_at' => $user->ban_expires_at,
                'created_at' => $user->created_at,
                'profile_picture' => $user->profile_picture,
                'username' => $user->username,
            ];
        });
        return response()->json(['users' => $users]);
    }

    public function show($id)
    {
        $user = User::with(['wishlists', 'reviews'])->findOrFail($id);
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'user',
            'total_reviews' => $user->reviews ? $user->reviews->count() : 0,
            'is_banned' => $user->is_banned,
            'ban_reason' => $user->ban_reason,
            'ban_expires_at' => $user->ban_expires_at,
            'created_at' => $user->created_at,
            'profile_picture' => $user->profile_picture,
            'username' => $user->username,
        ];
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'status' => 'required|in:active,inactive',
        ]);
        $user = new User($validated);
        $user->password = bcrypt($validated['password']);
        $user->save();
        $user->refresh();
        return response()->json(['message' => 'User created', 'user' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }
        $user->fill($validated);
        $user->save();
        $user->refresh();
        return response()->json(['message' => 'User updated', 'user' => $user]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }

    public function ban(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'reason' => 'required|string',
            'duration' => 'required',
        ]);
        $user->is_banned = true;
        $user->ban_reason = $validated['reason'];
        if ($validated['duration'] === 'permanent') {
            $user->ban_expires_at = null;
        } else {
            $days = (int) $validated['duration'];
            $user->ban_expires_at = now()->addDays($days);
        }
        $user->save();
        return response()->json(['message' => 'User banned', 'user' => $user], 200);
    }

    public function unban(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->is_banned = false;
        $user->ban_reason = null;
        $user->ban_expires_at = null;
        $user->save();
        return response()->json(['message' => 'User unbanned', 'user' => $user], 200);
    }

    public function dashboardStats()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOf6MonthsAgo = $now->copy()->subMonths(5)->startOfMonth();

        // Users
        $totalUsers = \App\Models\User::count();
        $newUsers = \App\Models\User::where('created_at', '>=', $startOfMonth)->count();

        // Books
        $totalBooks = \App\Models\Book::count();
        $newBooks = \App\Models\Book::where('created_at', '>=', $startOfMonth)->count();

        // Reviews
        $totalReviews = \App\Models\Review::count();
        $newReviews = \App\Models\Review::where('created_at', '>=', $startOfMonth)->count();

        // Genres
        $totalGenres = \App\Models\Genre::count();
        $newGenres = \App\Models\Genre::where('created_at', '>=', $startOfMonth)->count();

        // Top Genres (by number of books)
        $topGenres = \App\Models\Genre::withCount('books')
            ->orderByDesc('books_count')
            ->take(5)
            ->get(['id', 'name', 'books_count']);

        // User activity (users registered per month for last 6 months)
        $userActivity = \App\Models\User::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('created_at', '>=', $startOf6MonthsAgo)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Book activity (books added per month for last 6 months)
        $bookActivity = \App\Models\Book::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('created_at', '>=', $startOf6MonthsAgo)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Review activity (reviews added per month for last 6 months)
        $reviewActivity = \App\Models\Review::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('created_at', '>=', $startOf6MonthsAgo)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top reviewers (users with most reviews)
        $topReviewers = \App\Models\User::withCount('reviews')
            ->orderByDesc('reviews_count')
            ->take(5)
            ->get(['id', 'name', 'reviews_count', 'profile_picture', 'username']);

        // Most reviewed books
        $mostReviewedBooks = \App\Models\Book::withCount('reviews')
            ->orderByDesc('reviews_count')
            ->take(5)
            ->get(['id', 'title', 'author', 'cover_image', 'reviews_count']);

        // Recently added books
        $recentBooks = \App\Models\Book::orderByDesc('created_at')
            ->take(5)
            ->get(['id', 'title', 'author', 'cover_image', 'created_at']);

        // Recently registered users
        $recentUsers = \App\Models\User::orderByDesc('created_at')
            ->take(5)
            ->get(['id', 'name', 'profile_picture', 'username', 'created_at']);

        return response()->json([
            'total_users' => $totalUsers,
            'new_users_month' => $newUsers,
            'total_books' => $totalBooks,
            'new_books_month' => $newBooks,
            'total_reviews' => $totalReviews,
            'new_reviews_month' => $newReviews,
            'total_genres' => $totalGenres,
            'new_genres_month' => $newGenres,
            'top_genres' => $topGenres,
            'user_activity' => $userActivity,
            'book_activity' => $bookActivity,
            'review_activity' => $reviewActivity,
            'top_reviewers' => $topReviewers,
            'most_reviewed_books' => $mostReviewedBooks,
            'recent_books' => $recentBooks,
            'recent_users' => $recentUsers,
        ]);
    }
}
