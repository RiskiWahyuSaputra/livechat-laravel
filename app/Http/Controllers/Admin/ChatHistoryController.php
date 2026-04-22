<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatHistoryController extends Controller
{
    public function index(Request $request)
    {
        $archivesQuery = Conversation::onlyTrashed()
            ->with(['customer', 'admin'])
            ->latest('deleted_at');

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $archivesQuery->whereHas('customer', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $archivesQuery->where('problem_category', $request->string('category')->toString());
        }

        if ($request->input('filter') === 'my_chat') {
            $archivesQuery->where('admin_id', auth('admin')->id());
        }

        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->string('date_range')->toString());

            if (count($dates) === 2) {
                try {
                    $startDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                    $endDate = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                    $archivesQuery->whereBetween('deleted_at', [$startDate, $endDate]);
                } catch (\Throwable $e) {
                    // Ignore invalid date ranges and keep the rest of the filters working.
                }
            }
        }

        $archives = $archivesQuery->paginate(10)->withQueryString();

        $problemCategories = Conversation::onlyTrashed()
            ->whereNotNull('problem_category')
            ->where('problem_category', '!=', '')
            ->distinct()
            ->orderBy('problem_category')
            ->pluck('problem_category');

        return view('admin.history.index', compact('archives', 'problemCategories'));
    }

    public function show($id)
    {
        $conversation = Conversation::onlyTrashed()
            ->with([
                'customer',
                'admin',
                'messages' => fn ($query) => $query->orderBy('created_at'),
            ])
            ->findOrFail($id);

        return view('admin.history.show', compact('conversation'));
    }
}
