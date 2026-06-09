<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Genre;

class AdminGenreController extends Controller
{
    public function index(Request $request)
    {
        $genres = Genre::withCount('books')->get();
        $genres = $genres->map(function ($genre) {
            return [
                'id' => $genre->id,
                'name' => $genre->name,
                'description' => $genre->description,
                'status' => $genre->status ?? 'active',
                'total_books' => $genre->books_count,
                'created_at' => $genre->created_at,
                'updated_at' => $genre->updated_at,
            ];
        });
        return response()->json(['genres' => $genres]);
    }

    public function show($id)
    {
        $genre = Genre::withCount('books')->findOrFail($id);
        $data = [
            'id' => $genre->id,
            'name' => $genre->name,
            'description' => $genre->description,
            'status' => $genre->status ?? 'active',
            'total_books' => $genre->books_count,
            'created_at' => $genre->created_at,
            'updated_at' => $genre->updated_at,
        ];
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);
        $genre = Genre::create($validated);
        $genre->refresh();
        return response()->json(['message' => 'Genre created', 'genre' => $genre], 201);
    }

    public function update(Request $request, $id)
    {
        $genre = Genre::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
        $genre->fill($validated);
        $genre->save();
        $genre->refresh();
        return response()->json(['message' => 'Genre updated', 'genre' => $genre]);
    }

    public function destroy($id)
    {
        $genre = Genre::findOrFail($id);
        $genre->delete();
        return response()->json(['message' => 'Genre deleted']);
    }
}
