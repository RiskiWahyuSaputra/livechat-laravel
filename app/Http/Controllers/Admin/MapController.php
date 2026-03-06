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
        $origins = User::select('origin', DB::raw('count(*) as count'))
            ->whereNotNull('origin')
            ->where('origin', '!=', '')
            ->groupBy('origin')
            ->orderByDesc('count')
            ->get();

        return response()->json($origins);
    }
}
