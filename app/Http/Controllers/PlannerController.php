<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetBlockRequest;
use App\Services\PlannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlannerController extends Controller
{
    public function __construct(private PlannerService $planner)
    {
    }

    public function setBlock(SetBlockRequest $request): JsonResponse
    {
        $d = $request->validated();

        return $this->guard(function () use ($request, $d) {
            $this->planner->setBlock($request->user(), (int) $d['block_index'], $d['kind'], isset($d['task_id']) ? (int) $d['task_id'] : null);
            return ['ok' => true];
        });
    }

    public function endDay(Request $request): JsonResponse
    {
        return $this->guard(fn () => ['ok' => true] + $this->planner->endDay($request->user()));
    }
}
