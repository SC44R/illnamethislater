<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GameStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The pixel room page and the state the browser needs to draw it. */
class RoomController extends Controller
{
    public function __construct(private GameStateService $state)
    {
    }

    private function stateFor(?User $user): array
    {
        return $user ? $this->state->forUser($user) : $this->state->forGuest();
    }

    public function index(Request $request)
    {
        return view('room', ['state' => $this->stateFor($request->user())]);
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json($this->stateFor($request->user()));
    }
}
