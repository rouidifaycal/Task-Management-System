<?php

use App\Http\Middleware\EnsureTeamMembership;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Models\Team;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::model('current_team', Team::class);

Route::get('/dashboard', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

Route::prefix('{current_team}')
    ->middleware(['auth', EnsureTeamMembership::class])
    ->scopeBindings()
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::resource('projects', ProjectController::class);
        Route::resource('projects.tasks', TaskController::class);
    });

require __DIR__.'/settings.php';
