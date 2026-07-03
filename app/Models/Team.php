<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'city',
        'location',
        'game_date',
        'game_time',
        'gender',
        'age_from',
        'age_to',
        'players_needed',
        'description',
        'creator_id',
    ];

    protected $casts = [
        'game_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function requests()
    {
        return $this->hasMany(TeamJoinRequest::class);
    }
}
