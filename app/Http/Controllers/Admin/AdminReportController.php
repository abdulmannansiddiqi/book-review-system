<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::with(['review', 'user', 'book'])->get();
        $reports = $reports->map(function ($report) {
            return [
                'id' => $report->id,
                'type' => $report->type,
                'reason' => $report->reason,
                'status' => $report->status ?? 'pending',
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
                'review' => $report->review,
                'user' => $report->user,
                'book' => $report->book,
            ];
        });
        return response()->json(['reports' => $reports]);
    }

    public function show($id)
    {
        $report = Report::with(['review', 'user', 'book'])->findOrFail($id);
        $data = [
            'id' => $report->id,
            'type' => $report->type,
            'reason' => $report->reason,
            'status' => $report->status ?? 'pending',
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at,
            'review' => $report->review,
            'user' => $report->user,
            'book' => $report->book,
        ];
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:review,user,book',
            'reason' => 'required|string',
            'status' => 'required|in:pending,resolved,dismissed',
            'review_id' => 'nullable|exists:reviews,id',
            'user_id' => 'nullable|exists:users,id',
            'book_id' => 'nullable|exists:books,id',
        ]);
        $report = Report::create($validated);
        $report->refresh();
        return response()->json(['message' => 'Report created', 'report' => $report], 201);
    }

    public function update(Request $request, $id)
    {
        $report = Report::findOrFail($id);
        $validated = $request->validate([
            'type' => 'sometimes|required|in:review,user,book',
            'reason' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:pending,resolved,dismissed',
            'review_id' => 'nullable|exists:reviews,id',
            'user_id' => 'nullable|exists:users,id',
            'book_id' => 'nullable|exists:books,id',
        ]);
        $report->fill($validated);
        $report->save();
        $report->refresh();
        return response()->json(['message' => 'Report updated', 'report' => $report]);
    }

    public function destroy($id)
    {
        $report = Report::findOrFail($id);
        $report->delete();
        return response()->json(['message' => 'Report deleted']);
    }

    public function moderate($id) { return response()->json(['message' => 'AdminReportController@moderate']); }
}
