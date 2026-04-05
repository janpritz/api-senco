<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class ViewTransactionController extends Controller
{
    public function index(Request $request)
    {
        // Eager load the 'collector' relationship to get the name
        $transactions = Payment::with('collector')
            ->latest()
            ->paginate(15); // Returns data, links, and meta

        return response()->json($transactions);
    }

    public function getTransactions(Request $request)
    {
        $query = Payment::query();

        // CRITICAL: Filter by the collector ID passed from the frontend
        if ($request->has('collected_by')) {
            $query->where('collected_by', $request->collected_by);
        }

        // Handle Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%$search%")
                    ->orWhere('student_id', 'like', "%$search%");
            });
        }

        return $query->latest()->paginate(15);
    }
}
