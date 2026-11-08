<?php

namespace App\Http\Controllers;

use App\Exceptions\GameRuleException;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /** Runs $fn and turns a broken game rule into a 422 answer with a short error code. */
    protected function guard(callable $fn): JsonResponse
    {
        try {
            return response()->json($fn());
        } catch (GameRuleException $e) {
            return response()->json(['error' => $e->errorCode], 422);
        }
    }
}
