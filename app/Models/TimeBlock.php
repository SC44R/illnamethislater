<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeBlock extends Model
{
    protected $guarded = [];

    protected $casts = ['used' => 'boolean'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(DayPlan::class, 'day_plan_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
