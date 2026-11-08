<?php

namespace App\Http\Controllers;

use App\Models\ProcrastinatorType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    /** The player picks a procrastinator type once. It sets the starting stats. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['type_id' => ['required', 'integer', 'exists:procrastinator_types,id']]);
        $user = $request->user();

        if ($user->procrastinator_type_id) {
            return response()->json(['error' => 'type_already_chosen'], 422);
        }
        $user->forceFill(['procrastinator_type_id' => ProcrastinatorType::findOrFail($data['type_id'])->id])->save();

        return response()->json(['ok' => true]);
    }
}
