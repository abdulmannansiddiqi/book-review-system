<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminBookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::with(['genres', 'reviews'])->get();
        $books = $books->map(function ($book) {
            $cover = $book->cover_image;
            if (!$cover || $cover === 'default-book.jpg') {
                $cover = '/OBRAdminFront/img/default-book.jpg';
            }
            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'description' => $book->description,
                'isbn' => $book->isbn,
                'publication_year' => $book->publication_year,
                'status' => $book->status,
                'cover_image' => $cover,
                'genres' => $book->genres,
                'reviews' => $book->reviews,
                'average_rating' => $book->reviews->avg('rating') ?? 0,
                'total_reviews' => $book->reviews->count(),
                'created_at' => $book->created_at,
                'updated_at' => $book->updated_at,
            ];
        });
        return response()->json(['books' => $books]);
    }

    public function show($id)
    {
        $book = Book::with(['genres', 'reviews'])->findOrFail($id);
        $data = [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'description' => $book->description,
            'isbn' => $book->isbn,
            'publication_year' => $book->publication_year,
            'status' => $book->status,
            'cover_image' => $book->cover_image,
            'genres' => $book->genres,
            'reviews' => $book->reviews,
            'average_rating' => $book->reviews->avg('rating') ?? 0,
            'total_reviews' => $book->reviews->count(),
            'created_at' => $book->created_at,
            'updated_at' => $book->updated_at,
        ];
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'description' => 'required|string',
            'isbn' => 'required|string|max:255',
            'publication_year' => 'required|integer',
            'status' => 'required|in:active,inactive',
            'genres' => 'required|array',
            'genres.*' => 'exists:genres,id',
            'cover_image' => 'nullable|image|max:2048',
        ]);

        $book = new Book($validated);
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('covers', 'public');
            $book->cover_image = '/storage/' . $path;
        }
        $book->save();
        $book->genres()->sync($validated['genres']);
        $book->load('genres', 'reviews');
        $data = [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'description' => $book->description,
            'isbn' => $book->isbn,
            'publication_year' => $book->publication_year,
            'status' => $book->status,
            'cover_image' => $book->cover_image,
            'genres' => $book->genres,
            'reviews' => $book->reviews,
            'average_rating' => $book->reviews->avg('rating') ?? 0,
            'total_reviews' => $book->reviews->count(),
            'created_at' => $book->created_at,
            'updated_at' => $book->updated_at,
        ];
        return response()->json(['message' => 'Book created', 'book' => $data], 201);
    }

    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'isbn' => 'sometimes|required|string|max:255',
            'publication_year' => 'sometimes|required|integer',
            'status' => 'sometimes|required|in:active,inactive',
            'genres' => 'sometimes|array',
            'genres.*' => 'exists:genres,id',
            'cover_image' => 'nullable|image|max:2048',
        ]);

        $book->fill($validated);
        if ($request->hasFile('cover_image')) {
            // Delete old cover if exists
            if ($book->cover_image && file_exists(public_path($book->cover_image))) {
                @unlink(public_path($book->cover_image));
            }
            $path = $request->file('cover_image')->store('covers', 'public');
            $book->cover_image = '/storage/' . $path;
        }
        $book->save();
        if (isset($validated['genres'])) {
            $book->genres()->sync($validated['genres']);
        }
        $book->load('genres', 'reviews');
        $data = [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'description' => $book->description,
            'isbn' => $book->isbn,
            'publication_year' => $book->publication_year,
            'status' => $book->status,
            'cover_image' => $book->cover_image,
            'genres' => $book->genres,
            'reviews' => $book->reviews,
            'average_rating' => $book->reviews->avg('rating') ?? 0,
            'total_reviews' => $book->reviews->count(),
            'created_at' => $book->created_at,
            'updated_at' => $book->updated_at,
        ];
        return response()->json(['message' => 'Book updated', 'book' => $data]);
    }

    public function destroy($id)
    {
        $book = Book::findOrFail($id);
        // Optionally delete cover image
        if ($book->cover_image && file_exists(public_path($book->cover_image))) {
            @unlink(public_path($book->cover_image));
        }
        $book->delete();
        return response()->json(['message' => 'Book deleted']);
    }
}
