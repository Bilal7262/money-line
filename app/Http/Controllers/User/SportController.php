<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Http\Request;

class SportController extends Controller
{
    public function showSports()
    {
        $sports = Sport::all();

        return response()->json([
            'message' => 'Sports retrieved successfully',
            'sports' => $sports,
        ]);
    }

    public function showTeams()
    {
        $teams = Team::with('sport')->get();

        return response()->json([
            'message' => 'Teams retrieved successfully',
            'teams' => $teams,
        ]);
    }

    public function getTeamsBySport(Request $request)
    {
        $validated = $request->validate([
            'sport_ids' => 'required|array',
            'sport_ids.*' => 'exists:sports,id',
        ]);

        $teams = Team::with('sport')
            ->whereIn('sport_id', $validated['sport_ids'])
            ->get();

        return response()->json([
            'message' => 'Teams retrieved successfully',
            'teams' => $teams,
        ]);
    }
}
