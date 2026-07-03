<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::latest()->get();

        return view('teams.index', compact('teams'));
    }

    public function find(Request $request)
    {
        $query = Team::query();

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('date')) {
            $query->whereDate('game_date', $request->date);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        $teams = $query->latest()->get();

        return view('teams.find', compact('teams'));
    }

    public function create()
    {
        return view('teams.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'city' => 'required|max:255',
            'location' => 'nullable|max:255',
            'game_date' => 'required|date',
            'game_time' => 'required',
            'gender' => 'required',
            'age_from' => 'nullable|integer',
            'age_to' => 'nullable|integer',
            'players_needed' => 'required|integer',
            'description' => 'nullable',
        ]);

        Team::create([
            'title' => $request->title,
            'city' => $request->city,
            'location' => $request->location,
            'game_date' => $request->game_date,
            'game_time' => $request->game_time,
            'gender' => $request->gender,
            'age_from' => $request->age_from,
            'age_to' => $request->age_to,
            'players_needed' => $request->players_needed,
            'description' => $request->description,
            'creator_id' => 1,
        ]);

        return redirect('/teams')->with('success', 'تیم با موفقیت ثبت شد.');
    }
}
