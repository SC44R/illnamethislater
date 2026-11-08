<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// The room (login happens on the laptop screen inside it)
Route::get('/', [RoomController::class, 'index']);
Route::get('/state', [RoomController::class, 'state']);
Route::get('/login', fn () => redirect('/'))->name('login');   // where the auth middleware sends guests

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/character', [CharacterController::class, 'store']);

    Route::post('/tasks', [TaskController::class, 'store']);
    Route::post('/planner/blocks', [PlannerController::class, 'setBlock']);
    Route::post('/planner/end-day', [PlannerController::class, 'endDay']);

    Route::post('/blocks/{index}/start', [SessionController::class, 'start'])->whereNumber('index');
    Route::post('/sessions/{session}/roll', [SessionController::class, 'roll']);
    Route::post('/sessions/{session}/round', [SessionController::class, 'round']);
    Route::post('/sessions/{session}/dismiss', [SessionController::class, 'dismiss']);
    Route::post('/sessions/{session}/abandon', [SessionController::class, 'abandon']);
});

// Developer helpers (local only): set the level to try higher distraction tiers, or reset the day.
if (app()->isLocal()) {
    Route::middleware('auth')->group(function () {
        Route::get('/dev/level/{n}', function (int $n) {
            auth()->user()->forceFill(['level' => max(1, min(10, $n))])->save();
            return redirect('/');
        });
    });
}
