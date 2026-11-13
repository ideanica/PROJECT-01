<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StatusHistory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StatusHistoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'trackable_type' => ['nullable', 'string', Rule::in(array_keys(StatusHistory::TRACKABLE_TYPES))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $histories = StatusHistory::query()
            ->with(['trackable', 'oldStatus', 'newStatus', 'changedBy'])
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->search($term))
            ->when($filters['trackable_type'] ?? null, fn ($query, $type) => $query->where(
                'trackable_type',
                StatusHistory::TRACKABLE_TYPES[$type]['class']
            ))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.histories.index', [
            'histories' => $histories,
            'trackableTypes' => StatusHistory::TRACKABLE_TYPES,
        ]);
    }
}
