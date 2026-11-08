<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Register, login and logout. The forms live on the laptop screen in the room. */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        $user = User::create($data);
        $user->forceFill(['game_date' => now()->toDateString()])->save();   // game day 1 is today

        Auth::login($user);
        $request->session()->regenerate();

        // regenerate() also gives the session a NEW csrf token, so the browser must be told about it
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'credentials'], 422);
        }
        $request->session()->regenerate();

        // regenerate() also gives the session a NEW csrf token, so the browser must be told about it
        return response()->json(['ok' => true, 'csrf' => csrf_token()]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
