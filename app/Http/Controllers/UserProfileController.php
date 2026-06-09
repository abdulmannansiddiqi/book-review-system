<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Review;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_picture' => $user->profile_picture,
            'bio' => $user->bio,
            'notification_enabled' => $user->notification_enabled,
            'total_reviews' => $user->reviews()->count(),
            'average_rating' => $user->reviews()->avg('rating') ?? 0,
            'wishlist_count' => $user->wishlists()->count(),
            'preferred_genres' => $user->preferredGenres->pluck('id'),
            'preferred_genres_names' => $user->preferredGenres->pluck('name'),
        ]);
    }

    public function reviews(Request $request)
    {
        $user = $request->user();
        $reviews = $user->reviews()->where('status', 'approved')->with('book')->paginate(5);
        return response()->json([
            'reviews' => $reviews->items(),
            'total' => $reviews->total(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'notification_enabled' => 'nullable|boolean',
            'preferred_genres' => 'nullable|array',
            'preferred_genres.*' => 'exists:genres,id',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (array_key_exists('bio', $validated)) {
            $user->bio = $validated['bio'];
        }
        if (array_key_exists('notification_enabled', $validated)) {
            $user->notification_enabled = $validated['notification_enabled'];
        }
        $user->save();

        if (isset($validated['preferred_genres'])) {
            $user->preferredGenres()->sync($validated['preferred_genres']);
        }

        // Reload genres
        $user->load('preferredGenres');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_picture' => $user->profile_picture,
                'bio' => $user->bio,
                'notification_enabled' => $user->notification_enabled,
                'preferred_genres' => $user->preferredGenres->pluck('id'),
                'preferred_genres_names' => $user->preferredGenres->pluck('name'),
            ]
        ]);
    }

    public function uploadProfilePicture(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'profile_picture' => 'required|image|max:2048',
        ]);

        // Delete old profile picture if exists and is not default
        if ($user->profile_picture && !str_contains($user->profile_picture, 'default-avatar.jpg')) {
            $oldPath = public_path($user->profile_picture);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $user->profile_picture = '/storage/' . $path;
        $user->save();

        return response()->json([
            'message' => 'Profile picture updated',
            'profile_picture' => url('/storage/' . $path),
        ]);
    }
}
