<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function submitReferral(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string|max:10'
        ]);

        $user = $request->user();
        $user->update(['referral_code' => $request->referral_code]);

        return response()->json(['message' => 'Referral code saved']);
    }

    public function completeProfile(Request $request)
    {
        $request->validate([
             'username' => ['required', 'alpha_dash', 'max:255', 'unique:users,username,' . $request->user()->id],
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        $user = $request->user();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('profiles');
            $user->image = Storage::url($path); // Store full URL;
        }

        $user->update([
            'username' => $request->username,
            'name' => $request->name,
            'is_profile_complete' => true,
        ]);

        return response()->json(['message' => 'Profile completed', 'user' => $user]);
    }
    
    public function selectSports(Request $request)
    {
        $validated = $request->validate([
            'sport_ids' => 'required|array',
            'sport_ids.*' => 'exists:sports,id',
        ]);

        $user = $request->user();
        $user->sports()->sync($validated['sport_ids']);

        return response()->json([
            'message' => 'Sports selected successfully',
            'user' => $user->load('sports'),
        ]);
    }

    public function selectTeams(Request $request)
    {
        $validated = $request->validate([
            'team_ids' => 'required|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $user = $request->user();
        $teamIds = $validated['team_ids'];
        $validTeamIds = Team::whereIn('id', $teamIds)
            ->whereIn('sport_id', $user->sports->pluck('id'))
            ->pluck('id')
            ->toArray();

        if (count($validTeamIds) !== count($teamIds)) {
            return response()->json(['message' => 'Some teams are not associated with your selected sports'], 422);
        }

        $user->teams()->sync($validTeamIds);

        return response()->json([
            'message' => 'Teams selected successfully',
            'user' => $user->load('teams.sport'),
        ]);
    }
    
    public function showProfile(){
        return response()->json([
            'user' => auth()->user(),
        ]);
    }
}
