<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionDistractionEvent extends Model
{
    protected $guarded = [];

    protected $casts = ['resolved_at' => 'datetime'];

    public function distraction(): BelongsTo
    {
        return $this->belongsTo(Distraction::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TaskSession::class, 'task_session_id');
    }
}
