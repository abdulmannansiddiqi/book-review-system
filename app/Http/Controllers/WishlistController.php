<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display the user's wishlist.
     */
    public function index()
    {
        try {
            $wishlist = Wishlist::with(['book.genres', 'book.reviews'])
                ->where('user_id', Auth::id())
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'book_id' => $item->book_id,
                        'book' => $item->book,
                        'created_at' => $item->created_at
                    ];
                });

            return response()->json([
                'items' => $wishlist,
                'total' => $wishlist->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch wishlist'], 500);
        }
    }

    /**
     * Add a book to the wishlist.
     */
    public function add(Book $book)
    {
        try {
            $user = Auth::user();

            // Check if book is already in wishlist
            if ($user->wishlists()->where('book_id', $book->id)->exists()) {
                return response()->json(['message' => 'Book already in wishlist'], 400);
            }

            // Add to wishlist
            $user->wishlists()->create([
                'book_id' => $book->id
            ]);

            return response()->json(['message' => 'Book added to wishlist']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to add book to wishlist'], 500);
        }
    }

    /**
     * Remove a book from the wishlist.
     */
    public function remove(Book $book)
    {
        try {
            $user = Auth::user();

            // Remove from wishlist
            $user->wishlists()->where('book_id', $book->id)->delete();

            return response()->json(['message' => 'Book removed from wishlist']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to remove book from wishlist'], 500);
        }
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $bookId = $request->input('book_id');

        // Check if already in wishlist
        $existing = $user->wishlists()->where('book_id', $bookId)->first();
        if ($existing) {
            return response()->json(['message' => 'Book already in wishlist', 'item' => $existing], 200);
        }

        $item = $user->wishlists()->create(['book_id' => $bookId]);
        return response()->json(['message' => 'Book added to wishlist', 'item' => $item], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $item = $user->wishlists()->findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Book removed from wishlist']);
    }
}
