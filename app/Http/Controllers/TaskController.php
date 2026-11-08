<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Services\PlannerService;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(private PlannerService $planner)
    {
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->planner->createTask($request->user(), $request->validated());

        return response()->json(['ok' => true, 'task_id' => $task->id]);
    }
}
