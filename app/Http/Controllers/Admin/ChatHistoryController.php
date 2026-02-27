<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ChatHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Conversation::onlyTrashed()->with(['customer', 'admin']);

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        // Filter by problem category
        if ($request->filled('category')) {
            $query->where('problem_category', $request->category);
        }

        // Filter by date range
        if ($request->filled('date_range')) {
            $dateRange = explode(' - ', $request->date_range);
            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();
            $query->whereBetween('deleted_at', [$startDate, $endDate]);
        }

        $archives = $query->latest('deleted_at')->paginate(15)->withQueryString();
        
        // Get all unique problem categories for the filter dropdown
        $problemCategories = Conversation::onlyTrashed()
            ->whereNotNull('problem_category')
            ->distinct()
            ->pluck('problem_category');

        return view('admin.history.index', compact('archives', 'problemCategories'));
    }

    public function show($id)
    {
        $conversation = Conversation::onlyTrashed()->with(['customer', 'admin', 'messages'])->findOrFail($id);
        return view('admin.history.show', compact('conversation'));
    }
}
