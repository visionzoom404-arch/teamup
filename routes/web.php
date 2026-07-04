<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamController;

Route::get('/', function () {
    return view('home');
});

Route::get('/teams', [TeamController::class, 'index']);
