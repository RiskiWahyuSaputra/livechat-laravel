<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    /**
     * Return origin distribution data as JSON for the choropleth map.
     */
    public function getMapData()
    {
        // Data for Choropleth (Provinces)
        $origins = User::select('origin', DB::raw('count(*) as count'))
            ->whereNotNull('origin')
            ->where('origin', '!=', '')
            ->groupBy('origin')
            ->orderByDesc('count')
            ->get();

        // Data for Dot Markers (Individual Users)
        $users = User::select('id', 'name', 'contact', 'origin', 'is_online')
            ->whereNotNull('origin')
            ->where('origin', '!=', '')
            ->get();

        return response()->json([
            'provinces' => $origins,
            'users' => $users
        ]);
    }
}
